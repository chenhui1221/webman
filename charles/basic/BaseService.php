<?php
/**
 * @author charles
 * @created 2023/10/30 11:42
 */

namespace charles\basic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @package charles\basic
 * Class BaseService
 * @method int count(array $where = [], bool $search = true) 获取符合条件的数据总数
 * @method int getCount(array $conditions = []) 获取符合条件的数据总数（不使用搜索器）
 * @method int getDistinctCount(array $where, $field, bool $search = true) 获取符合条件的不同值的数量
 * @method mixed find() 查找数据
 * @method array getColumn(array $where, string $field, string $key = null) 获取列的值
 * @method mixed getFieldValue($value, string $filed, ?string $valueKey = '', ?array $where = []) 获取字段的值
 * @method mixed getMax(array $where = [], string $field = '') 获取最大值
 * @method mixed getMin(array $where = [], string $field = '') 获取最小值
 * @method float sum(array $where, string $field, bool $search = false) 求和
 * @method bool delete($id, ?string $key = null) 删除数据
 * @method bool destroy(int $id) 销毁数据
 * @method array|Model|null getOne(array $where, ?string $field = '*', array $with = []) 获取一条数据（不使用搜索器）
 * @method string getTableName() 获取表名
 * @method bool save(array $data) 保存数据
 * @method Collection saveAll(array $data) 保存多条数据
 * @method mixed value(array $where, ?string $field = '') 获取字段的值
 * @method array|Model|null get($id, ?array $field = [], ?array $with = []) 获取一条数据
 * @method bool be($map, string $field = '') 检查数据是否存在
 * @method bool bcInc($key, string $incField, string $inc, string $keyField = null, int $acc = 2) 自增
 * @method bool bcDec($key, string $decField, string $dec, string $keyField = null, int $acc = 2) 自减
 * @method bool bc($key, string $incField, string $inc, string $keyField = null, int $type = 1, int $acc = 2) 自增/自减
 * @method BaseModel update($id, array $data, ?string $key = null) 更新数据
 * @method string|null batchUpdate(array $ids, array $data, ?string $key = null) 批量更新数据
 * @method LengthAwarePaginator selectModel(array $conditions = [], array $columns = ['*'], ?int $page = null, ?int $limit = 15, array $orders = ['id' => 'ASC'], ?array $with = [], bool $search = false, bool $paginate = false) 模型选择
 * @method LengthAwarePaginator selectListPage(array $conditions = [], array $columns = ['*'], ?int $page = null, ?int $limit = 15, array $orders = ['id' => 'ASC'], ?array $with = [], bool $search = false) 模型选择
 * /**
 * @method LengthAwarePaginator selectList(array $conditions = [], $columns = ['*'], ?int $page = null, ?int $limit = 15, array $orders = ['id' => 'ASC'], ?array $with = [], bool $search = false) 选择列表
 * /
 * /
 */
abstract class BaseService
{
    /**
     * @var
     */
    protected $dao;

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return call_user_func_array([$this->dao, $name], $arguments);
    }

}