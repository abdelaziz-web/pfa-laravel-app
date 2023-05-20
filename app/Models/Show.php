<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Show extends Model {
    use HasFactory;
    protected $table = "shows";
    protected $guarded = [];

    public function episodes(): HasMany {
        return $this->hasMany(Episode::class);
    }

    public function genres(): HasMany {
        return $this->hasMany(Genre::class);
    }

    public function editGenres($genres) {
        $this -> genres()->delete();
            
        $this -> genres() -> createMany(array_map(function($gerne) {
            return [
                "name" => $gerne,
                "show_id" => $this->id
            ];
        }, explode(",", $genres)));
    }

    public function getGenres() {
        return $this->genres()->get()->map(function ($genre) {
            return $genre -> name;
        })->toArray();
    }

    public function favorites(): HasMany {
        return $this->hasMany(Favorite::class);
    }

    public function isFavorite() {
        $user = Auth::user();

        if(!$user) return false;

        $favorite = $this -> favorites() -> where("user_id", $user->id)->first();

        if($favorite) return true;

        return false;
    }

    public function genre($genre) {
        return $this->hasOne(Genre::class)->where("name", $genre);
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function rating() {
        return $this->reviews->avg("rating");
    }

    public function populate() {
        $rating = $this->rating();
        
        return array_merge($this->toArray(), [
            "genres" => $this->getGenres(),
            "favorite" =>  $this->isFavorite(),
            "userRating" => $rating ? number_format($rating, 1, ".", "") : "Na",
            "userRating_num" => $rating
        ]);
    }
}
