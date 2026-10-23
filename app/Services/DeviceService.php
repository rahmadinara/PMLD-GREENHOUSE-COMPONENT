
<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Collection;

class DeviceService
{
    /**
     * Mengambil seluruh perangkat.
     */
    public function getAllDevices(): Collection
    {
        return Device::orderBy('name')->get();
    }

    /**
     * Mengambil detail satu perangkat.
     */
    public function getDevice(int $id): Device
    {
        return Device::findOrFail($id);
    }

    /**
     * Menyalakan atau mematikan perangkat.
     *
     * Catatan: ini mengubah status di database,
     * belum mengendalikan hardware fisik.
     */
    public function setPower(Device $device, bool $isOn): Device
    {
        return DB::transaction(function () use ($device, $isOn) {
            $currentDevice = Device::whereKey($device->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($currentDevice->status !== 'online') {
                throw ValidationException::withMessages([
                    'device' => 'Perangkat sedang offline.',
                ]);
            }

            if ($currentDevice->mode !== 'manual') {
                throw ValidationException::withMessages([
                    'mode' => 'Perangkat harus dalam mode manual untuk kontrol ON/OFF.',
                ]);
            }

            $currentDevice->is_on = $isOn;
            $currentDevice->last_command_at = now();
            $currentDevice->save();

            return $currentDevice->refresh();
        });
    }

    /**
     * Mengubah mode perangkat menjadi manual atau auto.
     */
    public function setMode(Device $device, string $mode): Device
    {
        if (!in_array($mode, ['manual', 'auto'], true)) {
            throw ValidationException::withMessages([
                'mode' => 'Mode hanya boleh manual atau auto.',
            ]);
        }

        return DB::transaction(function () use ($device, $mode) {
            $currentDevice = Device::whereKey($device->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentDevice->mode = $mode;
            $currentDevice->last_command_at = now();
            $currentDevice->save();

            return $currentDevice->refresh();
        });
    }

    /**
     * Mengubah status koneksi perangkat.
     *
     * Biasanya method ini nanti dipanggil oleh
     * integrasi IoT, bukan tombol ON/OFF dashboard.
     */
    public function setConnectionStatus(
        Device $device,
        string $status
    ): Device {
        if (!in_array($status, ['online', 'offline'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status hanya boleh online atau offline.',
            ]);
        }

        $device->status = $status;
        $device->save();

        return $device->refresh();
    }
}
