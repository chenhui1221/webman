<?php
/**
 * @author charles
 * @created 2023/10/30 11:01
 */

namespace charles\traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait ModelTrait
{
    /**
     * 时间段搜索器
     * @param Builder $query
     * @param $value
     * @param array $data
     */
    public function scopeTime(Builder $query, $value, $data)
    {
        if ($value) {
            $timeKey = $data['timeKey'] ?? 'created_at';

            if (is_array($value)) {
                $startTime = $value[0] ?? null;
                $endTime = $value[1] ?? null;
                if ($startTime || $endTime) {
                    $query->whereBetween($timeKey, [$startTime, $endTime]);
                }
            } elseif (is_string($value)) {
                switch ($value) {
                    case 'today':
                    case 'yesterday':
                        $query->whereDate($timeKey, Carbon::parse($value));
                        break;
                    case 'week':
                    case 'month':
                    case 'year':
                    case 'last week':
                    case 'last month':
                    case 'last year':
                        $query->whereBetween($timeKey, [Carbon::parse("first day of $value"), Carbon::parse("last day of $value")]);
                        break;
                    case 'quarter':
                        $query->whereBetween($timeKey, $this->getQuarter());
                        break;
                    case 'lately7':
                        $query->whereBetween($timeKey, [Carbon::now()->subDays(7), Carbon::now()]);
                        break;
                    case 'lately30':
                        $query->whereBetween($timeKey, [Carbon::now()->subDays(30), Carbon::now()]);
                        break;
                    default:
                        if (strpos($value, '-') !== false) {
                            [$startTime, $endTime] = array_map('trim', explode('-', $value));
                            if ($startTime && $endTime) {
                                $query->whereBetween($timeKey, [Carbon::parse($startTime), Carbon::parse($endTime)]);
                            } elseif (!$startTime && $endTime) {
                                $query->where($timeKey, '<', Carbon::parse($endTime));
                            } elseif ($startTime && !$endTime) {
                                $query->where($timeKey, '>=', Carbon::parse($startTime));
                            }
                        }
                        break;
                }
            }
        }
    }

    /**
     * 获取本季度时间范围
     * @param int $offset
     * @return array
     */
    public function getQuarter(int $offset = 0): array
    {
        $currentMonth = Carbon::now()->month;
        $baseMonth = $offset ? $currentMonth - ($offset * 3) : $currentMonth;
        $firstMonthOfQuarter = (int)(ceil($baseMonth / 3) - 1) * 3 + 1;

        $firstDay = Carbon::create(null, $firstMonthOfQuarter)->startOfQuarter();
        $lastDay = $firstDay->copy()->endOfQuarter();

        return [$firstDay, $lastDay];
    }

    /**
     * 获取某个字段内的值
     * @param $value
     * @param string $field
     * @param string|null $valueKey
     * @param array|null $where
     * @return mixed
     */
    public function getFieldValue(Builder $query, $value, string $field, ?string $valueKey = null, ?array $where = [])
    {
        $query->where($field, $value);

        if ($where) {
            foreach ($where as $condition) {
                $query->where(...$condition);
            }
        }

        return $query->value($valueKey ?? $field);
    }

}
