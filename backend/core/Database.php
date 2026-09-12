<?php
/**
 * 数据库单例封装
 * PHP 7.4兼容 - PDO预处理语句
 */

class Database
{
    /** @var Database|null */
    private static ?Database $instance = null;

    /** @var PDO */
    private PDO $pdo;

    /**
     * 私有构造函数 - 初始化PDO连接
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new RuntimeException('数据库连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 禁止克隆
     */
    private function __clone() {}

    /**
     * 禁止反序列化
     */
    public function __wakeup()
    {
        throw new RuntimeException('不能反序列化单例');
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取底层PDO实例
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * 执行原始查询（SELECT、SHOW等）
     *
     * @param string $sql
     * @param array  $params
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException('查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取单行数据
     *
     * @param string $sql
     * @param array  $params
     * @return array|false
     */
    public function fetch(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    /**
     * 获取所有行数据
     *
     * @param string $sql
     * @param array  $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 向表中插入一行数据
     *
     * @param string $table
     * @param array  $data  列 => 值 的关联数组
     * @return int  最后插入的ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(function ($col) {
            return ':' . $col;
        }, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        $this->query($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * 更新表中的行
     *
     * @param string $table
     * @param array  $data   要更新的列 => 值对
     * @param string $where  WHERE子句（使用命名占位符）
     * @param array  $params WHERE子句的参数
     * @return int  受影响的行数
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $setClauses = [];
        foreach ($data as $key => $value) {
            $placeholder = ':set_' . $key;
            $setClauses[] = '`' . $key . '` = ' . $placeholder;
            $params[$placeholder] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * 从表中删除行
     *
     * @param string $table
     * @param string $where  WHERE子句（使用命名占位符）
     * @param array  $params WHERE子句的参数
     * @return int  删除的行数
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * 统计符合条件的行数
     *
     * @param string $table
     * @param string $where  WHERE子句，空字符串表示不过滤
     * @param array  $params
     * @return int
     */
    public function count(string $table, string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM `' . $table . '`';
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        $row = $this->fetch($sql, $params);
        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

    /**
     * 开始事务
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * 预处理语句（用于复杂查询）
     */
    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * 获取最后插入的ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取上次查询影响的行数
     */
    public function rowCount(PDOStatement $stmt): int
    {
        return $stmt->rowCount();
    }
}
