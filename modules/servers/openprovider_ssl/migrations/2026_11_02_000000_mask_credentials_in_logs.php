<?php

use WHMCS\Database\Capsule;
use Illuminate\Database\Migrations\Migration;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

/**
 * One-time cleanup (INTGRTNS-597) of credentials logged in plaintext before INTGRTNS-590.
 *
 * TODO: Remove this migration in the next major version. It only cleans up existing data;
 * new log entries are already masked by ApiCall::__curlCall() and openprovider_ssl_maskSensitiveParams().
 *
 * Only the credential values are replaced; the rest of each row is left byte-for-byte unchanged.
 * Values that are empty or already masked (all '*') are skipped, so running it again changes nothing.
 */
class MaskCredentialsInLogs extends Migration
{
    const MASK = '********';
    const CHUNK_SIZE = 500;

    public function up()
    {
        $modsslLogs = $this->maskModsslLogs();
        $moduleLog = $this->maskModuleLogLogin() + $this->maskModuleLogParams();

        logActivity("Openprovider SSL: masked credentials in {$modsslLogs} modssl_logs and {$moduleLog} Module Log entries (INTGRTNS-597)");
    }

    public function down()
    {
        // Masked values cannot be restored.
    }

    // modssl_logs login rows: {"username":"..","password":".."} / {"data":{"token":".."}}
    private function maskModsslLogs()
    {
        if (!Capsule::schema()->hasTable('modssl_logs')) {
            return 0;
        }

        $query = Capsule::table('modssl_logs')
            ->where('action', 'Open Ssl Test Connection')
            ->where(function ($q) {
                $q->where('request', 'like', '%"password":"%')
                    ->orWhere('response', 'like', '%"token":"%');
            });

        return $this->maskRows('modssl_logs', $query, function ($value) {
            return $this->maskJson($value, ['password', 'token']);
        });
    }

    // Module Log login rows, stored by WHMCS with print_r(): [password] => .. / [token] => ..
    private function maskModuleLogLogin()
    {
        $query = Capsule::table('tblmodulelog')
            ->where('module', 'open provider ssl')
            ->where('action', 'open ssl test connection')
            ->where(function ($q) {
                $q->where('request', 'like', '%[password] => %')
                    ->orWhere('response', 'like', '%[token] => %');
            });

        return $this->maskRows('tblmodulelog', $query, function ($value) {
            return $this->maskPrintR($value, '\s*', ['password', 'token']);
        });
    }

    // Module Log rows from exception handlers that logged $params, matched on moduletype (or module + action for TestConnection, which has none).
    private function maskModuleLogParams()
    {
        $query = Capsule::table('tblmodulelog')
            ->where(function ($q) {
                $q->where('request', 'like', '%[moduletype] => openprovider_ssl%')
                    ->orWhere(function ($q) {
                        $q->where('module', 'openprovider_ssl')
                            ->where('action', 'openprovider_ssl_testconnection');
                    });
            })
            ->where(function ($q) {
                $q->where('request', 'like', '%[serverpassword] => %')
                    ->orWhere('request', 'like', '%[serveraccesshash] => %');
            });

        return $this->maskRows('tblmodulelog', $query, function ($value) {
            $value = $this->maskPrintR($value, '\s*', ['serverpassword', 'serveraccesshash']);
            // Top-level service password only; nested [password] keys (e.g. client details) are left as is.
            return $this->maskPrintR($value, ' {4}', ['password']);
        });
    }

    /**
     * Walks the matching rows by id and updates only the rows whose request/response changed.
     * Each row is a separate short update, so the tables are never locked for long.
     */
    private function maskRows($table, $query, callable $mask)
    {
        $updated = 0;

        $query->select('id', 'request', 'response')->chunkById(self::CHUNK_SIZE, function ($rows) use ($table, $mask, &$updated) {
            foreach ($rows as $row) {
                $changes = [];
                foreach (['request', 'response'] as $column) {
                    $masked = $mask((string) $row->$column);
                    if ($masked !== (string) $row->$column) {
                        $changes[$column] = $masked;
                    }
                }

                if ($changes) {
                    Capsule::table($table)->where('id', $row->id)->update($changes);
                    $updated++;
                }
            }
        });

        return $updated;
    }

    private function maskJson($value, array $keys)
    {
        $pattern = '/"(' . implode('|', $keys) . ')":"((?:[^"\\\\]|\\\\.)*)"/';

        return preg_replace_callback($pattern, function ($m) {
            return $this->isExposed($m[2]) ? '"' . $m[1] . '":"' . self::MASK . '"' : $m[0];
        }, $value);
    }

    private function maskPrintR($value, $indent, array $keys)
    {
        $pattern = '/^(' . $indent . '\[(?:' . implode('|', $keys) . ')\] => )([^\r\n]*)/m';

        return preg_replace_callback($pattern, function ($m) {
            return $this->isExposed($m[2]) ? $m[1] . self::MASK : $m[0];
        }, $value);
    }

    private function isExposed($value)
    {
        return $value !== '' && !preg_match('/^\*+$/', $value);
    }
}
