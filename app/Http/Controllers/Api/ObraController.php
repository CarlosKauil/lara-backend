<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Obra;
use App\Models\EstatusObra;
use App\Models\MensajeRechazo;
use Illuminate\Support\Facades\Storage;

class ObraController extends Controller
{
    // Listar obras: admin ve todas, artista solo las suyas
        public function index(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role === 'Admin') {
                // Admin ve todas las obras
                $obras = Obra::with(['user', 'area', 'estatus', 'mensajesRechazo.admin'])->get();
            } elseif ($user->role === 'Artista') {
                // Artista ve solo sus obras
                $obras = Obra::with(['user', 'area', 'estatus', 'mensajesRechazo.admin'])
                    ->where('user_id', $user->id)
                    ->get();
            } else {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            return response()->json($obras);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

        // Subir una nueva obra (solo artista)
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Artista') {
            return response()->json(['message' => 'Solo los artistas pueden subir obras'], 403);
        }

        // ✅ AGREGA ESTA VALIDACIÓN
        if (!$user->artist) {
            return response()->json(['message' => 'El usuario no tiene perfil de artista'], 400);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:jpg,png,jpeg,fbx,blend,wav,mp4',
            'genero_tecnica' => 'required|string|max:255',
            'anio_creacion' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
            'area_id' => 'required|exists:areas,id',
            'descripcion' => 'nullable|string',
        ]);

        $path = $request->file('archivo')->store('obras', 'public');

        $obra = Obra::create([
            'user_id' => $user->id, // <--- AGREGA ESTA LÍNEA
            'artist_id' => $user->artist->id, // ✅ Ahora es seguro acceder a esto
            'area_id' => $request->area_id,
            'nombre' => $request->nombre,
            'archivo' => $path,
            'genero_tecnica' => $request->genero_tecnica,
            'anio_creacion' => $request->anio_creacion,
            'descripcion' => $request->descripcion,
            'estatus_id' => 1, // 1: Pendiente
        ]);

        return response()->json($obra, 200);
    }

    // Ver detalles de una obra
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $obra = Obra::with(['artist.user', 'area', 'estatus', 'mensajesRechazo.admin'])->findOrFail($id);

        if ($user->role === 'Admin' || ($user->role === 'Artista' && $obra->artist_id == $user->artist->id)) {
            return response()->json($obra);
        }

        return response()->json(['message' => 'No autorizado'], 403);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'Admin') {
            return response()->json(['message' => 'Solo el admin puede aceptar o rechazar obras'], 403);
        }

        $obra = Obra::findOrFail($id);

        $request->validate([
            'estatus_id' => 'required|in:2,3', // 2: Aceptada, 3: Rechazada
            'mensaje_rechazo' => 'nullable|string|max:1000',
        ]);

        $obra->estatus_id = $request->estatus_id;
        $obra->save();

        // Si es rechazada, guarda el mensaje
        if ($request->estatus_id == 3 && $request->filled('mensaje_rechazo')) {
            MensajeRechazo::create([
                'obra_id' => $obra->id,
                'admin_id' => $user->id,
                'mensaje' => $request->mensaje_rechazo,
            ]);
        }

        return response()->json($obra->load(['estatus', 'mensajesRechazo.admin']));
    }

    // (Opcional) Eliminar una obra (solo admin o el artista dueño)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $obra = Obra::findOrFail($id);

        if ($user->role === 'Admin' || ($user->role === 'Artista' && $obra->artist_id == $user->artist->id)) {
            // Borra el archivo físico
            if ($obra->archivo && Storage::disk('public')->exists($obra->archivo)) {
                Storage::disk('public')->delete($obra->archivo);
            }
            $obra->delete();
            return response()->json(null, 204);
        }

        return response()->json(['message' => 'No autorizado'], 403);
    }

        
    public function pendientes(Request $request)
    {
        $user = $request->user(); // <-- Primero obtén el usuario

        \Log::info('Usuario autenticado:', [
            'id' => optional($user)->id,
            'role' => optional($user)->role,
        ]);

        if ($user->role !== 'Admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $obras = Obra::with(['user', 'area', 'estatus'])
            ->where('estatus_id', 1) // 1: Pendiente
            ->get();

        return response()->json($obras);
    }

        public function aceptadas(Request $request)
    {
        $user = $request->user();
        // Solo admin puede ver todas, artista solo las suyas aceptadas
        if ($user->role === 'Admin') {
            $obras = Obra::with(['user', 'area', 'estatus'])
                ->where('estatus_id', 2) // 2: Aceptada
                ->get();
        } elseif ($user->role === 'Artista') {
            $obras = Obra::with(['user', 'area', 'estatus'])
                ->where('estatus_id', 2)
                ->where('user_id', $user->id)
                ->get();
        } else {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($obras);
    }


}