<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use SgFlores\SchemaSetting\Traits\HasSettings;

class TestTeam extends Model
{
    use HasSettings;

    protected $table = 'teams';

    protected $fillable = ['name'];
}
