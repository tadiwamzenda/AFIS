<?php

namespace Modules\AdmmInventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;
use Modules\AdmmInventory\Models\Client;

class EncryptLegacyApiKeysCommand extends Command
{
    protected $signature = 'admm:encrypt-legacy-api-keys {--dry-run}';

    protected $description = 'One-time migration: encrypt any navixy_api_key values stored before the encrypted cast existed';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Raw query builder — deliberately bypasses Eloquent's cast layer,
        // so this can inspect the true stored value without the encrypted
        // cast trying (and failing) to decrypt a legacy plaintext value.
        $rows = DB::table('clients')
            ->whereNotNull('navixy_api_key')
            ->where('navixy_api_key', '!=', '')
            ->get(['id', 'name', 'navixy_api_key']);

        $legacy = [];
        foreach ($rows as $row) {
            try {
                Crypt::decryptString($row->navixy_api_key);
                // Decrypts cleanly — already migrated, nothing to do.
            } catch (DecryptException $e) {
                $legacy[] = $row;
            }
        }

        if (empty($legacy)) {
            $this->info('No legacy plaintext keys found — nothing to migrate.');
            return self::SUCCESS;
        }

        $this->info(($isDryRun ? '[DRY RUN] ' : '') . count($legacy) . ' client(s) with a legacy plaintext key:');
        $this->table(['ID', 'Name'], array_map(fn($r) => [$r->id, $r->name], $legacy));

        if ($isDryRun) {
            $this->info('Dry run only — no changes made. Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Encrypt these ' . count($legacy) . ' key(s) now?', false)) {
            $this->info('Cancelled — no changes made.');
            return self::SUCCESS;
        }

        foreach ($legacy as $row) {
            // Deliberately NOT going through Eloquent here (Client::find()
            // + ->save()) — Laravel's dirty-checking for an 'encrypted'
            // cast must decrypt BOTH the new and the stored-original value
            // to compare them meaningfully (ciphertext differs every time
            // even for identical plaintext, so a raw comparison is
            // useless). The stored original here IS the raw plaintext —
            // not a valid encrypted payload — so that internal decrypt
            // attempt throws, confirmed via direct testing. Writing
            // through the raw query builder bypasses cast/dirty-checking
            // entirely, sidestepping the problem.
            DB::table('clients')
                ->where('id', $row->id)
                ->update(['navixy_api_key' => Crypt::encryptString($row->navixy_api_key)]);
            $this->line("  ✓ Encrypted key for {$row->name}");
        }

        $this->info('Done. ' . count($legacy) . ' key(s) encrypted.');
        return self::SUCCESS;
    }
}