<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Show;
use App\Models\Genre;
use Illuminate\Support\Facades\File; 

class ShowController extends Controller {

    public function index(Request $request) {
        $search = $request->input("search");
        $page = $request->input("page") ?: 0;
        $max = $request->input("max") ?: 20;
        $target = $request->input("target");

        $query = Show::latest()->select("id", "poster", "title", "runTime", "type", "rating", "keywords", "releaseDate", "created_at");

        if($target) $query = $query->where("type", $target);
        if($search) $query = $query->where("title", "like", "%" . $request->input("search") . "%");

        $count = $query->count();
        $tvCount = (clone $query)->where("type", "TV SHOW")->count();
        $filmCount = (clone $query)->where("type", "Film")->count();

        $shows =  $query->skip($page * $max)->take($max)->get();
        
        return view("admin/shows/index")->with([
            "count" => $count,
            "tvCount" => $tvCount,
            "filmCount" => $filmCount,
            "shows" => $shows->map(function($show) {
                return $show->populate();
            }),
            "search" => $search,
            "page" => $page,
            "max" => $max,
            "target" => $target
        ]);
    }

    public function add(Request $request) {
        $genres = Genre::list();

        return view("admin/shows/add")->with("genres", $genres);
    }

    public function edit($showID) {
        $show = Show::find($showID);
        $genres = Genre::list();

        return view("admin/shows/add", [
            "show" => $show->populate(),
            "genres" => $genres
        ]);
    }

    public function create(Request $request) {
        $request->validate([
            "title" => "required",
            "description" => "required",
            "type" => "required",
            "rating" => "required",
            "runTime" => "required",
            "releaseDate" => "required|date",
            "keywords" => "required",
            "genres" => "required",
            "poster" => "required|image|mimes:jpg,png,jpeg,gif,svg"
        ]);

        $posterName = time().".".$request->poster->getClientOriginalExtension();
        $request->poster->move(public_path("posters"), $posterName);

        $show = new Show(array_merge($request->except(["poster", "genres"]), [
            "poster" => $posterName
        ]));

        $show->save();

        $show->editGenres($request->genres);

        return redirect("admin/shows") -> with("status", "Show has been created successfuly");
    }

    public function delete($showID) {
        Show::destroy($showID);

        return redirect("/admin/shows") -> with("status", "Client has been deleted successfuly");
    }

    public function update($showID, Request $request) {
        $request->validate([
            "title" => "required",
            "description" => "required",
            "type" => "required",
            "rating" => "required",
            "runTime" => "required",
            "releaseDate" => "required|date",
            "keywords" => "required",
            "genres" => "required",
            "poster" => "image|mimes:jpg,png,jpeg,gif,svg"
        ]);

        $show = Show::find($showID);

        if($request->poster) {
            $posterName = time().".".$request->poster->getClientOriginalExtension();
            $request->poster->move(public_path("posters"), $posterName);
            File::delete(public_path("posters") . "/" . $show -> poster);
        }

        $show->editGenres($request->genres);

        $show->update(array_merge($request -> poster ? [
            "poster" => $posterName
        ] : [], $request -> except(["poster", "genres"])));

        return redirect("/admin/shows")->with("status", "Show have been updated successfuly");
    }
}
