<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
use App\Models\Audit\AccountBalanceOperation;
use App\Models\UserDB\GameUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class CoinAdminRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
    ) {}

    /**
     * @return array{username: string, coins: int, time: int}|null
     */
    public function find(string $username): ?array
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        if ($this->database->demo() || ! $this->database->userdbOnline()) {
            if (strcasecmp($username, 'admin') === 0) {
                return ['username' => 'admin', 'coins' => 0, 'time' => 0];
            }

            return null;
        }

        $user = GameUser::query()->where('Username', $username)->first();
        if (! $user) {
            return null;
        }

        return [
            'username' => (string) $user->Username,
            'coins' => (int) ($user->UserCoin ?? 0),
            'time' => (int) ($user->UserTime ?? 0),
        ];
    }

    /**
     * Ajuste de saldo: valor inteiro (positivo ou negativo), nunca zero.
     * Débito recusa saldo insuficiente. Tudo vai para auditoria local.
     *
     * @return array{username: string, coins: int, time: int, before: int, after: int}
     */
    public function adjustBalance(string $username, string $currency, int|string $delta, string $reason, string $operator, ?string $ip, string $idempotencyKey): array
    {
        $currency = strtolower(trim($currency));
        if (! in_array($currency, ['coins', 'time'], true)) {
            throw new DomainException('Moeda inválida.');
        }
        if (! preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $idempotencyKey)) {
            throw new DomainException('A chave idempotente deve ter 8 a 100 caracteres seguros.');
        }
        if (! is_numeric($delta) || (string) (int) $delta !== (string) $delta && ! is_int($delta)) {
            throw new DomainException('O delta precisa ser um inteiro.');
        }
        $delta = (int) $delta;
        if ($delta === 0) {
            throw new DomainException('O delta não pode ser zero.');
        }
        if (strlen(trim($reason)) < 5) {
            throw new DomainException('Informe um motivo com pelo menos 5 caracteres.');
        }
        if ($this->database->demo() || ! $this->database->userdbOnline()) {
            throw new DomainException('UserDB indisponível. Ajuste de coins exige SQL Server.');
        }

        $existing = AccountBalanceOperation::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->username !== $username || $existing->currency !== $currency || (int) $existing->delta !== $delta) {
                throw new DomainException('A chave idempotente já foi usada para outra operação.');
            }
            return $this->resultFromOperation($existing);
        }

        return DB::connection('userdb')->transaction(function () use ($username, $currency, $delta, $reason, $operator, $ip, $idempotencyKey) {
            $user = GameUser::query()->where('Username', $username)->lockForUpdate()->first();
            if (! $user) {
                throw new DomainException('Conta não encontrada.');
            }

            $column = $currency === 'coins' ? 'UserCoin' : 'UserTime';
            $before = (int) ($user->{$column} ?? 0);
            $after = $before + $delta;
            if ($after < 0) {
                throw new DomainException('Saldo insuficiente para debitar '.$currency.'.');
            }
            GameUser::query()->where('Username', $username)->update([$column => $after]);

            $operation = AccountBalanceOperation::query()->create([
                'idempotency_key' => $idempotencyKey,
                'username' => $username,
                'currency' => $currency,
                'delta' => $delta,
                'before_balance' => $before,
                'after_balance' => $after,
                'operator' => $operator,
                'ip' => $ip,
                'reason' => $reason,
                'status' => 'completed',
            ]);

            ConfigAudit::query()->create([
                'operator' => $operator, 'ip' => $ip,
                'action' => $delta > 0 ? 'balance.grant' : 'balance.remove',
                'resource' => 'UserDB.Users.'.$column, 'target_key' => $username,
                'value_before' => (string) $before, 'value_after' => (string) $after,
                'note' => $reason.'; idempotency='.$idempotencyKey, 'result' => 'ok',
            ]);

            return $this->resultFromOperation($operation, (int) ($user->UserCoin ?? 0), (int) ($user->UserTime ?? 0), $currency, $after);
        });
    }

    public function adjustCoins(string $username, int $delta, string $reason, string $operator, ?string $ip): array
    {
        return $this->adjustBalance($username, 'coins', $delta, $reason, $operator, $ip, 'legacy-'.sha1($username.'|'.$delta.'|'.$reason.'|'.microtime(true)));
    }

    private function resultFromOperation(AccountBalanceOperation $operation, ?int $coins = null, ?int $time = null, ?string $currency = null, ?int $after = null): array
    {
        if ($coins === null || $time === null) {
            $account = $this->find($operation->username);
            $coins = (int) ($account['coins'] ?? 0);
            $time = (int) ($account['time'] ?? 0);
        }
        if ($currency === 'coins') {
            $coins = $after ?? $coins;
        } elseif ($currency === 'time') {
            $time = $after ?? $time;
        }
        return [
            'username' => (string) $operation->username,
            'coins' => $coins,
            'time' => $time,
            'before' => (int) $operation->before_balance,
            'after' => (int) $operation->after_balance,
            'currency' => (string) $operation->currency,
            'idempotent' => true,
        ];
    }
}
