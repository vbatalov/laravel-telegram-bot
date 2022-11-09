<?php

namespace App\Models\fgislk_bot\fgislk_bot;

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

    public function checkUser()
    {
        $cid = $this->cid;

        if (!DB::table('users')->where('cid', '=', "$cid")->exists()) {
            return $this->createUser();
        }
    }

    public function createUser() {
        DB::table('users')->insert([
            'cid' => $this->cid ?? null,
            'firstname' => $this->firstname ?? null,
        ]);
    }

    /**
     * @param $cid
     * @param $city
     */
    public function setupCity ($city) {
        $cid = $this->cid;

         DB::table('users')->where('cid', '=', "$cid")->update([
            'city' => $city
        ]);
    }
}
