<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class MediaController extends Controller
{
    /**
     * Display a listing of the media.
     */
    public function index()
    {
        $media = Media::latest()->paginate(24);
        return view('admin.media', compact('media'));
    }

    /**
     * Store an uploaded file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        $path = $file->store('media', 'public');

        $thumbnailPath = null;
        $optimizedPath = null;

        // If this is an image, generate thumbnail and optimized version
        if (str_starts_with($mime, 'image')) {
            try {
                // Create Intervention Image instance
                $img = Image::make($file->getRealPath())->orientate();

                // Generate optimized version: re-encode with quality (keep original dimensions but lower quality)
                $optimized = (clone $img)->encode('jpg', 82);
                $optimizedFilename = 'media/optimized_' . uniqid() . '.jpg';
                Storage::disk('public')->put($optimizedFilename, (string) $optimized);
                $optimizedPath = $optimizedFilename;

                // Generate thumbnail: constrained to 400x300
                $thumb = (clone $img)->fit(400, 300, function ($constraint) {
                    $constraint->upsize();
                })->encode('jpg', 78);
                $thumbFilename = 'media/thumbnails/thumb_' . uniqid() . '.jpg';
                Storage::disk('public')->put($thumbFilename, (string) $thumb);
                $thumbnailPath = $thumbFilename;
            } catch (\Exception $e) {
                // If processing fails, continue without thumbnail/optimized
                report($e);
            }
        }

        $media = Media::create([
            'filename' => $originalName,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'optimized_path' => $optimizedPath,
            'mime_type' => $mime,
            'size' => $size,
            'uploaded_by' => null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['media' => $media], 201);
        }

        return back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Media $media)
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        return back()->with('success', 'File deleted.');
    }
}
