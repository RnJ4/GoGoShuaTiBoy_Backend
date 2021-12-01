<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class favouriteModel extends Model
{
    use HasFactory;
    protected $table = 'favourite';
    use QueryCacheable;
    public $cacheFor = 1200;
    public $cacheTags = ['favourite'];
    public $cacheDriver = 'redis';
    protected static $flushCacheOnUpdate = true;
    public function getCacheTagsToInvalidateOnUpdate($relation = null, $pivotedModels = null): array
    {
        return [
            "fav:{$this->user}"
        ];
    }

}
