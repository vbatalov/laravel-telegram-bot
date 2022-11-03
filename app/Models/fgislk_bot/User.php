<?php

namespace App\Models\fgislk_bot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\fgislk_bot\Main;

class User extends Model
{
    use HasFactory;

    private $cid;
    private $firstname;
    private $lastname;
    private $username;

    public function __construct($cid, $firstname = null, $lastname = null, $username = null) {
        $this->cid = $cid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->username = $username;
    }

    public function checkUser() {
        $cid = $this->cid;
        if (!DB::table('users')->where('cid', '=', "$cid")->exists()) {
            $this->createUser();
        }
    }

    public function createUser() {
        DB::table('users')->insert([
            'cid' => $this->cid ?? null,
            'firstname' => $this->firstname ?? null,
        ]);
    }
}
