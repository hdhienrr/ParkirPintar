<?php

namespace App\Models;

use App\Enums\StatusBooking;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $table = 'booking';

    protected $fillable = [
        'user_id',
        'kode_booking',
        'slot_id',
        'plat',
        'jenis',
        'waktu_mulai',
        'waktu_selesai',
    ];

    protected $attributes = [
        'status' => 'dipesan',
        'denda' => 0,
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'menunggu_sejak' => 'datetime',
            'denda_mulai' => 'datetime',
            'waktu_keluar' => 'datetime',
            'denda' => 'integer',
            'status' => StatusBooking::class,
        ];
    }

    /** Disimpan dengan format yang sama seperti kendaraan_parkir: huruf besar tanpa spasi. */
    protected function plat(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => KendaraanParkir::normalisasiPlat($value),
        );
    }

    /**
     * Penanda hitungan, bukan status tersimpan: kendaraan masih di dalam setelah waktu selesai.
     */
    protected function melewatiWaktu(): Attribute
    {
        return Attribute::get(fn () => $this->status === StatusBooking::Aktif && now()->greaterThan($this->waktu_selesai));
    }

    /** Pemilik boleh membatalkan selama belum check-in dan jam mulai belum tiba. */
    public function bisaDibatalkan(): bool
    {
        return $this->status === StatusBooking::Dipesan && now()->lessThan($this->waktu_mulai);
    }

    /** Kendaraan dari booking yang dibatalkan sistem belum tercatat keluar. */
    public function menungguTandaiKeluar(): bool
    {
        return $this->status === StatusBooking::DibatalkanSistem && $this->waktu_keluar === null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(SlotParkir::class, 'slot_id');
    }

    public function kendaraan(): HasOne
    {
        return $this->hasOne(KendaraanParkir::class);
    }

    public function penyebab(): BelongsTo
    {
        return $this->belongsTo(self::class, 'penyebab_booking_id');
    }

    public function scopeBerlaku(Builder $query): void
    {
        $query->whereIn('status', StatusBooking::berlaku());
    }

    /**
     * Booking yang jendelanya tumpang tindih dengan rentang ini, atau berjarak kurang dari jeda minimal.
     */
    public function scopeBentrokDengan(Builder $query, CarbonInterface $mulai, CarbonInterface $selesai): void
    {
        $jeda = config('parkir.booking.jeda_menit');

        $query->where('waktu_mulai', '<', $selesai->copy()->addMinutes($jeda))
            ->where('waktu_selesai', '>', $mulai->copy()->subMinutes($jeda));
    }

    public function getRouteKeyName(): string
    {
        return 'kode_booking';
    }
}
