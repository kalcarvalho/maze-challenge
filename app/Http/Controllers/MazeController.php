<?php

namespace App\Http\Controllers;

use App\Models\Maze;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MazeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function save(Request $request)
    {
        $array = ['error' => ''];

        $userId = Auth::id();

        $data = $request->only([
            'entrance',
            'gridSize',
            'walls',
        ]);

        $validator = Validator::make($data, [
            'entrance' => ['required', 'string', 'regex:/^(?:[A-Z]|[A-Z][A-Z]|[A-X][A-F][A-D])(?:[1-9]|[1-9][0-9]|[1-9][0-9][0-9]|[1-9][0-9][0-9][0-9]|[1-9][0-9][0-9][0-9][0-9]|[1-9][0-9][0-9][0-9][0-9][0-9]|10[0-3][0-9][0-9][0-9][0-9]|104[0-7][0-9][0-9][0-9]|1048[0-4][0-9][0-9]|10485[0-6][0-9]|104857[0-6])$/', 'min:2'],
            'gridSize' => ['required', 'string', 'regex:/^\d+x\d+$/', 'min:3'],
            'walls' => ['required', 'json'],
        ]);


        if ($validator->fails()) {
            $array['error'] = $validator->getMessageBag();
            return $array;
        }

        $gridSize = $data['gridSize'];

        $cell = explode('x', $gridSize);

        if ($cell[0] == 0 || $cell[1] == 0) {
            $array['error'] = 'The grid size must be at least 1x1.';
            return $array;
        }
        
        $maze = new Maze();
        $maze->user_id = $userId;
        $maze->entrance = $data['entrance'];
        $maze->grid_size = $data['gridSize'];
        $maze->walls = $data['walls'];
        $maze->save();
        
        $array['data'] = ['message' => 'Your Maze has been sucessfully created!'];
        $array['data'] = ['success' => true];

        return $array;
    }

    public function list(Request $request) {

        $array = ['error' => ''];

        $userId = Auth::id();

        $mazes = Maze::where('user_id', '=', $userId)->get();

        $data = [];

        foreach ($mazes as $maze) {
            $data[] = [
                'id' => $maze['id'],
                'entrance' => $maze['entrance'],
                'gridSize' => $maze['grid_size'],
                'walls' => $maze['walls'],
            ];
        }

        $array['mazes'] = $data;

        return $array;

    }

    public function solution(Request $request, $mazeId)
    {
        $array = ['error' => ''];

        $userId = Auth::id();

        $data = $request->only([
            'steps',
        ]);

        $maze = Maze::where('user_id', '=', $userId)->where('id', '=', $mazeId)->first();

        if(!$maze) {
            $array['error'] = 'Maze ID not found.';
            return $array;
        }

        $position = $maze['entrance'];
        $rc = str_split($position);

        $gridSize = explode('x', $maze['grid_size']);

        $heigth = $gridSize[0];
        $width = $gridSize[1];
        $walls = json_decode($maze['walls']);
        $hexit = chr(65 + $heigth - 1);
        $wexit = $rc[1] + intval($width) - 1;

        $exit = false;
        $path = [$maze['entrance']];

        for ($r = 1; $r <= $heigth; $r++) {

            for ($c = 1; $c <= $width; $c++) {

                $last_position = $position;

                $rc = str_split($position);

                for ($m = 1; $m <= 4; $m++) {

                    $doMove = $this->doMove($rc[1], $rc[0], $walls, $path, $hexit, $wexit, $m);

                    if ($doMove[3]) {
                        $position = $doMove[2];

                        array_push($path, $position);

                        if ($data['steps'] == 'min') {

                            if (str_contains($position, $hexit) || str_contains($position, $wexit)) {

                                $array['path'] = $path;
                                // $array['walls'] = $walls;

                                return $array;
                            }

                            break;
                        }

                        if ($data['steps'] == 'max') {

                            if ($position == $last_position) {

                                if (str_contains($position, $hexit) || str_contains($position, $wexit)) {
                                    $array['path'] = $path;
                                    // $array['walls'] = $walls;
                                }

                                return $array;
                            }

                            break;
                        }
                    }
                }
            }
        }

        
        // $array['walls'] = $walls;
        $array['path'] = $path;
        $size = sizeof($path);
        
        if($maze['entrance'] == $path[$size-1]) {
            $array['error'] = 'A Solution has not been found.';
            return $array;
        }
        
        if (!(str_contains($path[$size-1], $hexit) && str_contains($path[$size-1], $wexit))) {
            $array['error'] = 'A Solution has not been found.';
            return $array;
        }
        
        return $array;
    }

    public function doMove($row, $col, $walls, $path, $hexit, $wexit, $m)
    {

        $doMove = false;

        $old_pos = $col . $row;

        $r = 0;
        $c = 0;

        switch ($m) {
            case 1:
                if ($row == $wexit) { //last row
                    $new_pos = $col . $row;
                } else {
                    $new_pos = $col . ++$row;
                    $r = 1;
                }
                break; // down
            case 2:
                if ($col == $hexit) { //last column
                    $new_pos = $col . $row;
                } else {
                    $new_pos = ++$col . $row;
                    $c = 1;
                }
                break; // right
            case 3:
                if ($col == 'A') { //first column
                    $new_pos = $col . $row;
                } else {
                    $new_pos = chr(ord($col) - 1) . $row;
                    $c = -1;
                }
                break; // left
            case 4:
                if ($row == 1) { //first row
                    $new_pos = $col . $row;
                } else {
                    $new_pos = $col . --$row;
                    $r = -1;
                }

                break; // up
        }


        if (in_array($new_pos, $path)) {
            return [0, 0, $old_pos, false];
        }

        if ($new_pos == $old_pos) {
            return [0, 0, $old_pos, false];
        }

        if (in_array($new_pos, $walls)) {
            return [0, 0, $old_pos, false];
        }

        return [$r, $c, $new_pos, true];
    }
}
