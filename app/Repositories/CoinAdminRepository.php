<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
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
    public function adjustCoins(string $username, int $delta, string $reason, string $operator, ?string $ip): array
    {
        if ($delta === 0) {
            throw new DomainException('O delta não pode ser zero.');
        }
        if (strlen(trim($reason)) < 5) {
            throw new DomainException('Informe um motivo com pelo menos 5 caracteres.');
        }
        if ($this->database->demo() || ! $this->database->userdbOnline()) {
            throw new DomainException('UserDB indisponível. Ajuste de coins exige SQL Server.');
        }

        return DB::connection('userdb')->transaction(function () use ($username, $delta, $reason, $operator, $ip) {
            $user = GameUser::query()->where('Username', $username)->lockForUpdate()->first();
            if (! $user) {
                throw new DomainException('Conta não encontrada.');
            }

            $before = (int) ($user->UserCoin ?? 0);
            $after = $before + $delta;
            if ($after < 0) {
                throw new DomainException('Saldo insuficiente para debitar '.$delta.' coins.');
            }

            if ($delta > 0) {
                GameUser::query()->where('Username', $username)->update([
                    'UserCoin' => DB::raw('UserCoin + '.(int) $delta),
                ]);
            } else {
                $amount = abs($delta);
                $affected = GameUser::query()
                    ->where('Username', $username)
                    ->where('UserCoin', '>=', $amount)
                    ->update([
                        'UserCoin' => DB::raw('UserCoin - '.$amount),
                    ]);
                if ($affected === 0) {
                    throw new DomainException('Saldo insuficiente para debitar '.$amount.' coins.');
                }
            }

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => $delta > 0 ? 'coins.grant' : 'coins.remove',
                'resource' => 'UserDB.Users.UserCoin',
                'target_key' => $username,
                'value_before' => (string) $before,
                'value_after' => (string) $after,
                'note' => $reason,
                'result' => 'ok',
            ]);

            return [
                'username' => $username,
                'coins' => $after,
                'time' => (int) ($user->UserTime ?? 0),
                'before' => $before,
                'after' => $after,
            ];
        });
    }
}
