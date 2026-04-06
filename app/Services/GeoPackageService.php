<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class GeoPackageService
{
    /**
     * Salva o GeoPackage e extrai o valor bruto da geometria (MVP — ver dívida técnica WKB/WKT).
     *
     * @return array{wkt: string, caminho: string}
     */
    public function extrairWkt(UploadedFile $arquivo): array
    {
        $caminhoRelativo = Storage::disk('local')->putFile('geopackages', $arquivo);

        if ($caminhoRelativo === false) {
            throw new RuntimeException('Não foi possível salvar o arquivo GeoPackage.');
        }

        $caminhoAbsoluto = Storage::disk('local')->path($caminhoRelativo);

        try {
            $pdo = new PDO('sqlite:'.$caminhoAbsoluto, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $statement = $pdo->query('SELECT geometry FROM gpkg_contents LIMIT 1');

            if ($statement === false) {
                throw new RuntimeException('Falha ao consultar geometria no GeoPackage (gpkg_contents).');
            }

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row === false || ! array_key_exists('geometry', $row) || $row['geometry'] === null) {
                throw new RuntimeException('Nenhuma geometria encontrada no GeoPackage ou coluna ausente.');
            }

            $geometria = $row['geometry'];

            if (! is_string($geometria)) {
                $geometria = (string) $geometria;
            }

            return [
                'wkt' => $geometria,
                'caminho' => $caminhoRelativo,
            ];
        } catch (RuntimeException $e) {
            Storage::disk('local')->delete($caminhoRelativo);

            throw $e;
        } catch (PDOException $e) {
            Storage::disk('local')->delete($caminhoRelativo);

            throw new RuntimeException(
                'Não foi possível ler o GeoPackage como SQLite ou a tabela gpkg_contents é inválida: '.$e->getMessage(),
                0,
                $e
            );
        } catch (Throwable $e) {
            Storage::disk('local')->delete($caminhoRelativo);

            throw new RuntimeException(
                'Erro inesperado ao processar o GeoPackage: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
