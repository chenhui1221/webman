<?php
/**
 * @author charles
 * @created 2023/10/30 11:40
 */

namespace charles\basic;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use support\Container;
use charles\basic\BaseModel;
use \Illuminate\Database\Eloquent\Builder;

abstract class BaseDao
{
    protected $alias;
    protected $joinAlis;

    abstract protected function setModel(): string;

    /**
     * 获取模型
     * @return BaseModel
     */
    protected function getModel(): BaseModel
    {
        return Container::get($this->setModel());
    }

    /**
     * 设置join连表模型
     * @return string
     */
    protected function setJoinModel(): string
    {
    }

    /**
     * 读取数据条数
     * @param array $where
     * @param bool $search
     * @return int
     * @throws \ReflectionException
     */
    public function count(array $where = [], bool $search = true): int
    {
        return $this->search($where, $search)->count();
    }

    /**
     * 不走scope
     * @param array $conditions
     * @return int
     */
    public function getCount(array $conditions = []): int
    {
        return $this->getModel()->where($conditions)->count();
    }

    /**
     * 获取某些条件去重总数
     * @param array $where
     * @param $field
     * @param bool $search
     * @return int|mixed
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function getDistinctCount(array $where, $field, bool $search = true)
    {
        if ($search) {
            return $this->search($where)->distinct($field)->count($field);
        } else {
            return $this->getModel()->where($where)->distinct($field)->count($field);
        }
    }


    public function find()
    {

    }

    /**
     * 获取某个字段数组
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumn(array $where, string $field, string $key = null): array
    {
        return $this->getModel()->where($where)->pluck($field, $key)->toArray();
    }

    /**
     * 获取某个字段内的值
     * @param $value
     * @param string $filed
     * @param string|null $valueKey
     * @param array|string[] $where
     * @return mixed
     */
    public function getFieldValue($value, string $filed, ?string $valueKey = '', ?array $where = [])
    {
        return $this->getModel()->getFieldValue($value, $filed, $valueKey, $where);
    }

    /**
     * 获取条件数据中的某个值的最大值
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function getMax(array $where = [], string $field = '')
    {
        return $this->getModel()->where($where)->max($field);
    }

    /**
     * 获取条件数据中的某个值的最小值
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function getMin(array $where = [], string $field = '')
    {
        return $this->getModel()->where($where)->min($field);
    }

    /**
     * 求和
     * @param array $where
     * @param string $field
     * @param bool $search
     * @return float
     * @throws \ReflectionException
     */
    public function sum(array $where, string $field, bool $search = false)
    {
        if ($search) {
            return $this->search($where)->sum($field);
        } else {
            return $this->getModel()->where($where)->sum($field);
        }
    }

    /**
     * 删除
     * @param int|string|array $id
     * @return mixed
     */
    public function delete($id, ?string $key = null)
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }
        return $this->getModel()->where($where)->delete();
    }

    /**
     * 删除记录
     * @param int $id
     * @return int
     */
    public function destroy(int $id): int
    {
        return $this->getModel()->destroy($id);
    }

    /**
     * 根据条件获取一条数据
     * @param array $where
     * @param string|null $field
     * @param array $with
     * @return array|BaseModel|null
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function getOne(array $where, ?string $field = '*', array $with = [])
    {
        $field = explode(',', $field);
        return $this->get($where, $field, $with);
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->getModel()->getTable();
    }

    /**
     * 插入数据
     * @param array $data
     * @return mixed
     */
    public function save(array $data)
    {
        return $this->getModel()::create($data);
    }

    /**
     * 批量插入数据
     * @param array $data
     * @return bool
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->insert($data);
    }

    /**
     * 获取单个字段值
     * @param array $where
     * @param string|null $field
     * @return mixed
     */
    public function value(array $where, ?string $field = '')
    {
        $pk = $this->getPk();
        return $this->search($where)->value($field ?: $pk);
    }

    /**
     * 获取一条数据
     * @param $id
     * @param array|null $field
     * @param array|null $with
     * @return array|BaseModel|null
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function get($id, ?array $field = [], ?array $with = [])
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [$this->getPk() => $id];
        }
        return $this->getModel()->where($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->select($field ?? ['*'])->first();
    }

    /**
     * 查询一条数据是否存在
     * @param $map
     * @param string $field
     * @return bool 是否存在
     */
    public function be($map, string $field = ''): bool
    {
        if (!is_array($map) && empty($field)) $field = $this->getPk();
        $map = !is_array($map) ? [$field => $map] : $map;
        return 0 < $this->getModel()->where($map)->count();
    }

    /**
     * 高精度加法
     * @param $key
     * @param string $incField
     * @param string $inc
     * @param string|null $keyField
     * @param int $acc
     * @return bool
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function bcInc($key, string $incField, string $inc, string $keyField = null, int $acc = 2): bool
    {
        return $this->bc($key, $incField, $inc, $keyField, 1);
    }

    /**
     * 高精度 减法
     * @param $key
     * @param string $decField
     * @param string $dec
     * @param string|null $keyField
     * @param int $acc
     * @return bool
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function bcDec($key, string $decField, string $dec, string $keyField = null, int $acc = 2)
    {
        return $this->bc($key, $decField, $dec, $keyField, 2);
    }

    /**
     * 高精度计算并保存
     * @param $key
     * @param string $incField
     * @param string $inc
     * @param string|null $keyField
     * @param int $type
     * @param int $acc
     * @return bool
     * @throws RecordsNotFoundException
     * @throws RuntimeException
     * @throws ModelNotFoundException
     */
    public function bc($key, string $incField, string $inc, string $keyField = null, int $type = 1, int $acc = 2)
    {
        if ($keyField === null) {
            $result = $this->get($key);
        } else {
            $result = $this->getOne([$keyField => $key]);
        }
        if (!$result) return false;
        $new = 0;
        if ($type === 1) {
            $new = bcadd($result[$incField], $inc, $acc);
        } else if ($type === 2) {
            if ($result[$incField] < $inc) return false;
            $new = bcsub($result[$incField], $inc, $acc);
        }
        $result->{$incField} = $new;
        return false !== $result->save();
    }

    /**
     * 更新数据
     * @param int|string|array $id
     * @param array $data
     * @param string|null $key
     * @return bool|int
     */
    public function update($id, array $data, ?string $key = null)
    {
        // 如果提供的是数组，那么假设它已经是条件数组
        if (is_array($id)) {
            $where = $id;
        } else {
            // 如果没有提供 $key，则默认使用主键进行查找
            $where = [is_null($key) ? $this->getModel()->getKeyName() : $key => $id];
        }

        // 使用查询构造器进行更新操作
        return $this->getModel()->where($where)->update($data);
    }

    /**
     * 批量更新数据
     * @param array $ids
     * @param array $data
     * @param string|null $key
     * @return int
     */
    public function batchUpdate(array $ids, array $data, ?string $key = null): int
    {
        return $this->getModel()->whereIn(is_null($key) ? $this->getPk() : $key, $ids)->update($data);
    }

    /**
     * 获取主键
     * @return array|string
     */
    protected function getPk()
    {
        return $this->getModel()->getKeyName();
    }

    /**
     * @param array $conditions
     * @param $columns
     * @param int|null $page
     * @param int|null $limit
     * @param array $orders
     * @param array|null $with
     * @param bool $search
     * @return Collection
     */
    public function selectList(
        array  $conditions = [],
               $columns = ['*'],
        ?int   $page = null,
        ?int   $limit = 15,
        array  $orders = ['id' => 'ASC'],
        ?array $with = [],
        bool   $search = false
    ): Collection
    {
        return $this->selectModel($conditions, $columns, $page, $limit, $orders, $with, $search)->get();
    }

    /**
     * @param array $conditions
     * @param string[] $columns
     * @param int|null $page
     * @param int|null $limit
     * @param array $orders
     * @param array|null $with
     * @param bool $search
     * @return array
     */
    public function selectListPage(
        array  $conditions = [],
               $columns = ['*'],
        ?int   $page = null,
        ?int   $limit = 15,
        array  $orders = ['id' => 'ASC'],
        ?array $with = [],
        bool   $search = false
    ):  array
    {
        $results = $this->selectModel($conditions, $columns, $page, $limit, $orders, $with, $search,true);
        // 获取分页元数据
        $pagination = [
            'total'         => $results->total(),
            'per_page'      => $results->perPage(),
            'current_page'  => $results->currentPage(),
            'last_page'     => $results->lastPage(),
        ];

        // 返回数据和分页元数据
        return [
            'list'       => $results->items(),
            'page' => $pagination,
        ];
    }

    /**
     * @param array $conditions
     * @param array $columns
     * @param int|null $page
     * @param int|null $limit
     * @param array $orders
     * @param array|null $with
     * @param bool $search
     * @return Builder
     */
    /**
     * 获取模型数据，可选择是否分页。
     *
     * @param array $conditions 查询条件
     * @param array $columns 要获取的列
     * @param int|null $page 分页页码
     * @param int|null $limit 每页数量
     * @param array $orders 排序条件
     * @param array|null $with 关联加载
     * @param bool $search 是否使用搜索逻辑
     * @param bool $paginate 是否使用分页
     * @return LengthAwarePaginator|Builder|Collection
     */
    public function selectModel(
        array $conditions = [],
        array $columns = ['*'],
        ?int $page = null,
        ?int $limit = 15,
        array $orders = ['id' => 'ASC'],
        ?array $with = [],
        bool $search = false,
        bool $paginate = false
    ) {
        $model = $search ? $this->search($conditions) : $this->getModel()->where($conditions);

        $query = $model->select($columns)
            ->when($with, function (Builder $query, $with) {
                return $query->with($with);
            })
            ->when($orders, function (Builder $query, $orders) {
                foreach ($orders as $column => $direction) {
                    $query->orderBy($column, $direction);
                }
                return $query;
            });

        if ($paginate) {
            // 如果 $paginate 为 true，返回分页结果
            return $query->paginate($limit, $columns, 'page', $page);
        } else {
            // 否则根据是否传入 $page 和 $limit 参数决定返回结果
            return $page && $limit ? $query->forPage($page, $limit) : $query;
        }
    }

    protected function search(array $conditions, bool $search = true): Builder
    {
        $query = $this->getModel()->newQuery();

        if ($search) {
            foreach ($conditions as $field => $value) {
                $scopeMethod = 'scope' . Str::studly($field);
                if (method_exists($this->setModel(), $scopeMethod)) {
                    $query->$field($value);
                } else {
                    $query->where($field, $value);
                }
            }
        } else {
            $query->where($conditions);
        }

        return $query;
    }

}