<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Founder;
use App\Models\HelpVideo;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WebsiteContentController extends Controller
{
    public function manageFounder(Request $request)
    {
        $founder = Founder::orderBy('id', 'asc')->first();

        if ($request->isMethod('post')) {
            $newImagePath = $this->processImageUpload($request, 'founder');

            if ($newImagePath === false) {
                return back()->with('error', session('error'))->withInput();
            }

            $oldImagePath = $founder->image_path ?? null;

            $saveData = [
                'name' => trim((string) $request->input('name')),
                'designation' => trim((string) $request->input('designation')),
                'description' => trim((string) $request->input('description')),
                'display_status' => $request->boolean('display_status') ? 1 : 0,
            ];
            if ($newImagePath !== null) {
                $saveData['image_path'] = $newImagePath;
            }

            if ($founder) {
                $founder->fill($saveData);
                $founder->save();
            } else {
                $founder = Founder::create($saveData);
            }

            if ($newImagePath !== null && $oldImagePath && $oldImagePath !== $newImagePath) {
                $oldAbsolute = public_path($oldImagePath);
                if (File::exists($oldAbsolute)) {
                    File::delete($oldAbsolute);
                }
            }

            return redirect()->route('admin.website.founder')
                ->with('success', 'Founder details saved successfully.');
        }

        return view('admin.website.founder', compact('founder'));
    }

    public function managePartners(Request $request)
    {
        if ($request->isMethod('post')) {
            $newImagePath = $this->processImageUpload($request, 'partner');

            if ($newImagePath === false) {
                return back()->with('error', session('error'))->withInput();
            }

            $chosenName = trim((string) $request->input('name'));
            if ($chosenName === '__custom__') {
                $chosenName = trim((string) $request->input('name_custom'));
            }

            $maxOrder = (int) Partner::max('sort_order');

            Partner::create([
                'name' => $chosenName,
                'location' => trim((string) $request->input('location')),
                'company' => trim((string) $request->input('company')),
                'description' => trim((string) $request->input('description')),
                'image_path' => $newImagePath,
                'sort_order' => $maxOrder + 1,
                'display_status' => $request->boolean('display_status') ? 1 : 0,
            ]);

            return redirect()->route('admin.website.partners')
                ->with('success', 'Partner added successfully.');
        }

        $partners = Partner::orderBy('sort_order', 'asc')->get();
        $resellersList = User::where('access_level', 3)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->pluck('username');

        return view('admin.website.partners', compact('partners', 'resellersList'));
    }

    public function editPartner(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $newImagePath = $this->processImageUpload($request, 'partner');

            if ($newImagePath === false) {
                return back()->with('error', session('error'))->withInput();
            }

            $chosenName = trim((string) $request->input('name'));
            if ($chosenName === '__custom__') {
                $chosenName = trim((string) $request->input('name_custom'));
            }

            $oldImagePath = $partner->image_path;

            $saveData = [
                'name' => $chosenName,
                'location' => trim((string) $request->input('location')),
                'company' => trim((string) $request->input('company')),
                'description' => trim((string) $request->input('description')),
                'display_status' => $request->boolean('display_status') ? 1 : 0,
            ];
            if ($newImagePath !== null) {
                $saveData['image_path'] = $newImagePath;
            }

            $partner->fill($saveData);
            $partner->save();

            if ($newImagePath !== null && $oldImagePath && $oldImagePath !== $newImagePath) {
                $oldAbsolute = public_path($oldImagePath);
                if (File::exists($oldAbsolute)) {
                    File::delete($oldAbsolute);
                }
            }

            return redirect()->route('admin.website.partners')
                ->with('success', 'Partner updated successfully.');
        }

        $resellersList = User::where('access_level', 3)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->pluck('username');

        return view('admin.website.editPartner', compact('partner', 'resellersList'));
    }

    public function deletePartner($id)
    {
        $partner = Partner::findOrFail($id);
        $imagePath = $partner->image_path;

        $partner->delete();

        if ($imagePath) {
            $absolute = public_path($imagePath);
            if (File::exists($absolute)) {
                File::delete($absolute);
            }
        }

        return redirect()->route('admin.website.partners')
            ->with('success', 'Partner removed.');
    }

    public function manageHelpVideo(Request $request)
    {
        $video = HelpVideo::where('source_app', 'laravel')->first();

        if ($request->isMethod('post')) {
            $request->validate([
                'video' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg|max:51200',
                'title' => 'nullable|string|max:150',
            ]);

            $file = $request->file('video');
            $oldPath = $video->video_path ?? null;

            $uploadDir = public_path('files/help_videos');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            $filename = 'help_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);

            $saveData = [
                'source_app' => 'laravel',
                'title' => trim((string) $request->input('title')),
                'video_path' => 'files/help_videos/' . $filename,
                'display_status' => $request->boolean('display_status') ? 1 : 0,
            ];

            if ($video) {
                $video->fill($saveData);
                $video->save();
            } else {
                $video = HelpVideo::create($saveData);
            }

            if ($oldPath && $oldPath !== $saveData['video_path']) {
                $oldAbsolute = public_path($oldPath);
                if (File::exists($oldAbsolute)) {
                    File::delete($oldAbsolute);
                }
            }

            return redirect()->route('admin.website.helpVideo')
                ->with('success', 'Help video saved successfully.');
        }

        return view('admin.website.helpVideo', compact('video'));
    }

    public function deleteHelpVideo()
    {
        $video = HelpVideo::where('source_app', 'laravel')->first();

        if ($video) {
            if ($video->video_path) {
                $absolute = public_path($video->video_path);
                if (File::exists($absolute)) {
                    File::delete($absolute);
                }
            }
            $video->delete();
        }

        return redirect()->route('admin.website.helpVideo')
            ->with('success', 'Help video removed.');
    }

    /**
     * Validates and stores an uploaded image, re-encoding it through GD so a
     * disguised/polyglot file can't survive as a valid-looking image - mirrors
     * the CakePHP processFounderImageUpload()/processPartnerImageUpload() behavior.
     *
     * Returns null (no file uploaded), false (validation failed, flash set), or
     * the relative public path to store in image_path.
     */
    private function processImageUpload(Request $request, string $subfolder): null|false|string
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('image');

        if (!$file) {
            return null;
        }

        if (!$file->isValid()) {
            session()->flash('error', 'Image upload failed. Please try again.');
            return false;
        }

        $maxSizeBytes = 2 * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            session()->flash('error', 'Image is too large. Maximum allowed size is 2 MB.');
            return false;
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            session()->flash('error', 'The uploaded file is not a valid image.');
            return false;
        }

        $allowedMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mime = $imageInfo['mime'];
        if (!isset($allowedMimeToExt[$mime])) {
            session()->flash('error', 'Only JPG, PNG or WebP images are allowed.');
            return false;
        }
        $ext = $allowedMimeToExt[$mime];

        $uploadDir = public_path('files/' . $subfolder . 's');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $secureFileName = $subfolder . '_' . Str::random(16) . '.' . $ext;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $secureFileName;

        $file->move($uploadDir, $secureFileName);

        if ($mime === 'image/jpeg') {
            $img = @imagecreatefromjpeg($destination);
            if ($img) {
                imagejpeg($img, $destination, 88);
                imagedestroy($img);
            }
        } elseif ($mime === 'image/png') {
            $img = @imagecreatefrompng($destination);
            if ($img) {
                imagepng($img, $destination, 6);
                imagedestroy($img);
            }
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($destination);
            if ($img) {
                imagewebp($img, $destination, 85);
                imagedestroy($img);
            }
        }

        return 'files/' . $subfolder . 's/' . $secureFileName;
    }
}
