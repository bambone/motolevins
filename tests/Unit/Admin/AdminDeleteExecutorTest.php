<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Lifecycle\AdminDeleteExecutor;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDeleteExecutorTest extends TestCase
{
    #[Test]
    public function user_message_for_foreign_key_constraint_is_russian(): void
    {
        $pdo = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails');
        $pdo->errorInfo = ['23000', 1451, 'fk'];
        $e = new QueryException('mysql', 'delete from `x`', [], $pdo);

        $msg = AdminDeleteExecutor::userMessageForQueryException($e);

        $this->assertStringContainsString('связанные данные', $msg);
    }
}
