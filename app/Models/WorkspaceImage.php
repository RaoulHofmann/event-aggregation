<?php

namespace App\Models;

use App\Traits\MultiTenantModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceImage extends Model
{
    use HasUuids, MultiTenantModel, SoftDeletes;

    protected $fillable = ['name', 'mime', 'bytes'];

    public function storageKey(int $workspace): string
    {
        return "workspaces/{$workspace}/{$this->id}";
    }
}
