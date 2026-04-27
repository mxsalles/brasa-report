<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class RegistrarIncendioController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('registrar-incendio');
    }
}
