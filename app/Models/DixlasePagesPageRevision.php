<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase Pages is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\DixlasePages\App\Models;

use App\Models\Member;
use App\Services\RevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Page revision model
 *
 * Holds snapshots on save, manual backup, and pre-restoration backup.
 */
class DixlasePagesPageRevision extends Model
{
    protected $table = 'plg_dixlase_pages_revisions';

    public const TYPE_AUTO = RevisionService::TYPE_AUTO;

    public const TYPE_MANUAL = RevisionService::TYPE_MANUAL;

    public const TYPE_RESTORE_BACKUP = RevisionService::TYPE_RESTORE_BACKUP;

    /** @var list<string> */
    protected $fillable = [
        'page_id',
        'snapshot',
        'type',
        'note',
        'is_protected',
        'created_by',
    ];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'is_protected' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DixlasePagesPage, self>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(DixlasePagesPage::class, 'page_id');
    }

    /**
     * @return BelongsTo<Member, self>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (self $revision): void {
            if (empty($revision->created_at)) {
                $revision->created_at = now();
            }
        });
    }
}
