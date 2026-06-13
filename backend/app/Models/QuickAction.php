<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuickAction extends Model
{
    protected $fillable = [
        'key',
        'emoji',
        'label',
        'badge',
        'action_type',
        'prompt',
        'target_path',
        'target_title',
        'web_label',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuickActionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 转成小程序 chat.js 期望的 quickActions 数组项结构。
     * 结构与原写死数组完全一致，小程序拿到即可直接 setData。
     */
    public function toClientArray(): array
    {
        $node = [
            'key' => $this->key,
            'emoji' => $this->emoji ?? '',
            'label' => $this->label,
            'badge' => $this->badge ?? '',
        ];

        switch ($this->action_type) {
            case 'prompt':
                $node['prompt'] = $this->prompt ?? '';
                break;

            case 'web':
                $node['prompt'] = $this->prompt ?? '';
                $node['web'] = [
                    'path' => $this->target_path,
                    'title' => $this->target_title,
                    'label' => $this->web_label,
                ];
                break;

            case 'open':
                $node['open'] = [
                    'path' => $this->target_path,
                    'title' => $this->target_title,
                ];
                break;

            case 'route':
                $node['route'] = $this->target_path;
                break;

            case 'external':
                // 用 web-view 打开完整外部 URL；小程序 chat.js 读 action.external → report.js ?url=
                $node['external'] = $this->target_path;
                break;

            case 'home':
                // 返回主页（切换行业）：小程序 reLaunch 回落地页，清空页面栈
                $node['home'] = $this->target_path ?: '/pages/industry/industry';
                break;

            case 'menu':
                $node['items'] = $this->items->map(function (QuickActionItem $item) {
                    $sub = [
                        'key' => 'qai-'.$item->id,
                        'emoji' => $item->emoji ?? '',
                        'label' => $item->label,
                        'desc' => $item->desc ?? '',
                    ];
                    if ($item->item_type === 'route') {
                        $sub['route'] = $item->route;
                    } elseif ($item->item_type === 'external') {
                        $sub['external'] = $item->route;
                    } else {
                        $sub['prompt'] = $item->prompt ?? '';
                    }

                    return $sub;
                })->all();
                break;
        }

        return $node;
    }
}
