<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Dashboard\Datatables\AudioDatatables;
use App\Http\Controllers\Dashboard\Datatables\UserDatatables;
use App\Http\Requests\Dashboard\SettingsRequest;
use App\Repository\AudioRepository;
use App\Repository\PlaylistRepository;
use App\Repository\SettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
class AudioController extends BaseDashboardController
{
    protected $audioRepository;
    protected $playlistRepository;


    public function __construct(AudioRepository $audioRepository, PlaylistRepository $playlistRepository)
    {
        parent::__construct();

        $this->audioRepository = $audioRepository;

        $this->playlistRepository = $playlistRepository;

    }

    public function index(Request $request, AudioDatatables $dataTable){
        return $dataTable->render("dashboard::pages.audio.index");
    }

    public function create(Request $request){

        $settings = config("dashboard");

        return view("dashboard::pages.audio.create");
    }

    public function store(Request $request){
        $input = $request->all();

        $rules = array(
            'file' => 'required|mimes:mp3',
            'broadcast_date' => 'required',
            'type'  => 'required'
        );

        $validation = Validator::make($input, $rules);

        if ($validation->fails())
        {
            return Response::make($validation->errors()->first(), 400);
        }

        $broadcast_date = $request->get('broadcast_date');
        $type = $request->get('type');
        $user_id = Auth::id();
        $directory = 'upload/'.$user_id . '/'.$broadcast_date.'/'.$type;

        $path = $request->file('file')->store($directory);


        if( $path ) {
            $playlist = $this->playlistRepository->updateOrCreate([
                'broadcast_date' => $broadcast_date,
                'type'  => $type
            ],[
                'user_id' => $user_id,
                'ready' => 'pending',
                'folder' => $directory
            ]);


            $this->audioRepository->create([
                'name' => $request->file('file')->getClientOriginalName(),
                'path' => $path,
                'broadcast_date' => $broadcast_date,
                'type'  => $type,
                'user_id' => $user_id,
                'playlist_id' => $playlist->id
            ]);
            return Response::json('success', 200);
        } else {
            return Response::json('error', 400);
        }
    }

    public function delete($id){
        $audio = $this->audioRepository->findOrFail($id);

        if ($audio->delete()) {
            session()->flash('success', trans('dashboard.delete-success'));
            return response()->json(['message' => true], 200);
        } else {
            session()->flash('success', trans('dashboard.delete-fail'));

            return response()->json(['message' => false], 200);
        }

    }
}