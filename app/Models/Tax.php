<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    public function change($num,$flag)
    {
        if(!is_float($num))
            return 0;
        if($flag)
            $this->balance += $num*$this->taxPercent;
        else
            $this->balance -= $num*$this->taxPercent;
        $this->transactionNumber++;
        if(!$this->save())
            return 0;
        return 1;
    }
}
