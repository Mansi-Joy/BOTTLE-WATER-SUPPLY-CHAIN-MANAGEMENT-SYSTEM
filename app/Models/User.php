<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 'admin', 'supplier', 'manufacturer', 'retailer'
        'status',
        'phone',
        'address',
        'skills',
        'is_available',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'skills' => 'array',
        'is_available' => 'boolean',
    ];

    public function isAdmin()
    {
        return $this->role === 'administrator';
    }

    public function isSupplier()
    {
        return $this->role === 'supplier';
    }

    public function isManufacturer()
    {
        return $this->role === 'manufacturer';
    }

    public function isRetailer()
    {
        return $this->role === 'retailer';
    }

    public function getDashboardRoute()
    {
        return match($this->role) {
            'administrator' => 'admin.dashboard',
            'supplier' => 'supplier.dashboard',
            'manufacturer' => 'manufacturer.dashboard',
            'retailer' => 'retailer.dashboard',
            'vendor' => 'vendor.dashboard',
            default => 'home',
        };
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    // Task Management Relationships
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function taskComments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function taskTimeLogs()
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    public function tasksAssigned()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // Task Management Methods
    public function getActiveTasksCount()
    {
        return $this->assignedTasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    public function getOverdueTasksCount()
    {
        return $this->assignedTasks()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    public function getTodayTasksCount()
    {
        return $this->assignedTasks()
            ->whereDate('due_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    public function getWorkloadPercentage()
    {
        $totalTasks = $this->assignedTasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        
        $inProgressTasks = $this->assignedTasks()
            ->where('status', 'in_progress')
            ->count();
        
        return $totalTasks > 0 ? ($inProgressTasks / $totalTasks) * 100 : 0;
    }
}
