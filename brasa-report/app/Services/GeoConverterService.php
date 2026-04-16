<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Shapefile\ShapefileReader;
use Throwable;
use ZipArchive;

class GeoConverterService
{
    /**
     * Recebe um arquivo geoespacial e retorna GeoJSON como array (estrutura GeoJSON).
     * Formatos: .geojson, .json, .kml, .zip (shapefile), .shp (com sidecars no mesmo diretório de upload — preferir .zip).
     */
    public function toGeoJson(UploadedFile $file): array
    {
        $extensao = strtolower($file->getClientOriginalExtension());

        return match ($extensao) {
            'geojson', 'json' => $this->fromGeoJson($file),
            'kml' => $this->fromKml($file),
            'zip' => $this->fromShapefileZip($file),
            'shp' => $this->fromShapefileStandalone($file),
            default => throw new RuntimeException(
                "Formato '{$extensao}' não suportado. Use: geojson, json, kml, zip (shapefile) ou shp."
            ),
        };
    }

    // ---------------------------------------------------------------

    private function fromGeoJson(UploadedFile $file): array
    {
        $conteudo = file_get_contents($file->getRealPath());
        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo.');
        }

        $dados = json_decode($conteudo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('GeoJSON inválido: '.json_last_error_msg());
        }

        if (! is_array($dados) || ! isset($dados['type'])) {
            throw new RuntimeException('GeoJSON inválido: falta a propriedade "type".');
        }

        return $dados;
    }

    private function fromKml(UploadedFile $file): array
    {
        $conteudo = file_get_contents($file->getRealPath());
        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo KML.');
        }

        $geom = \geoPHP::load($conteudo, 'kml');

        if (! $geom) {
            throw new RuntimeException('Não foi possível interpretar o arquivo KML.');
        }

        $json = $geom->out('json');
        $dados = json_decode((string) $json, true);

        if (! is_array($dados)) {
            throw new RuntimeException('Falha ao converter KML para GeoJSON.');
        }

        return $dados;
    }

    private function fromShapefileZip(UploadedFile $file): array
    {
        $tmpDir = sys_get_temp_dir().'/shp_'.uniqid('', true);

        if (! mkdir($tmpDir, 0700, true) && ! is_dir($tmpDir)) {
            throw new RuntimeException('Não foi possível criar diretório temporário.');
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) !== true) {
                throw new RuntimeException('Não foi possível abrir o arquivo ZIP.');
            }

            $zip->extractTo($tmpDir);
            $zip->close();

            $shpFiles = $this->globRecursive($tmpDir, '*.shp');
            if ($shpFiles === []) {
                throw new RuntimeException('Nenhum arquivo .shp encontrado dentro do ZIP.');
            }

            return $this->shapefilePathsToGeoJson($shpFiles[0]);
        } finally {
            $this->deleteDirectory($tmpDir);
        }
    }

    private function fromShapefileStandalone(UploadedFile $file): array
    {
        $real = $file->getRealPath();
        if ($real === false) {
            throw new RuntimeException('Arquivo inválido.');
        }

        try {
            return $this->shapefilePathsToGeoJson($real);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Não foi possível ler o Shapefile. Envie um ZIP com .shp, .shx e .dbf juntos, ou verifique os arquivos.',
                0,
                $e
            );
        }
    }

    /**
     * Lê um shapefile e devolve um GeoJSON FeatureCollection.
     */
    private function shapefilePathsToGeoJson(string $shpPath): array
    {
        try {
            $reader = new ShapefileReader($shpPath);
        } catch (Throwable $e) {
            throw new RuntimeException('Não foi possível abrir o Shapefile: '.$e->getMessage(), 0, $e);
        }

        $features = [];

        foreach ($reader as $geometry) {
            if ($geometry === null) {
                continue;
            }
            $geoArr = json_decode($geometry->getGeoJSON(false, false), true);
            if (! is_array($geoArr)) {
                continue;
            }
            $features[] = [
                'type' => 'Feature',
                'geometry' => $geoArr,
                'properties' => [],
            ];
        }

        if ($features === []) {
            throw new RuntimeException('O Shapefile não contém geometrias legíveis.');
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * @return list<string>
     */
    private function globRecursive(string $dir, string $pattern): array
    {
        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $path = $fileInfo->getPathname();
            if (fnmatch($pattern, basename($path))) {
                $result[] = $path;
            }
        }

        return $result;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
