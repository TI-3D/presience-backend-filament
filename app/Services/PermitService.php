<?php

namespace App\Services;

use App\Contracts\PermitContract;
use App\Http\Requests\CurrentPermitRequest;
use App\Http\Requests\PermitAfterRequest;
use App\Http\Requests\PermitBeforeSchedule;
use App\Http\Resources\ApiResource;
use App\Services\AttendanceService;
use App\Services\ScheduleService;
use App\Utils\WebResponseUtils;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermitService implements PermitContract
{
    protected $scheduleService;
    protected $attendanceService;
    public function __construct(ScheduleService $scheduleService, AttendanceService $attendanceService)
    {
        $this->scheduleService = $scheduleService;
        $this->attendanceService = $attendanceService;
    }

    function currentPermit(CurrentPermitRequest $request)
    {
        try {
            $student_id = Auth::id();
            $sick = 0;
            $permit = 0;
            $today = Carbon::today()->format('Y-m-d');
            $scheduleWeek = $this->scheduleService->getScheduleById($request->sw_id);
            if (!$scheduleWeek) {
                throw new Exception("No schedules found for today.");
            }
            if ($scheduleWeek->closed_at) {
                throw new Exception("Permit not available.");
            }
            if ($request->permit_type === 'sakit') {
                $sick = $scheduleWeek->time;
            } elseif ($request->permit_type === 'izin') {
                $permit = $scheduleWeek->time;
            }
            $data = null;

            DB::transaction(function () use ($request, $today, $student_id, $scheduleWeek, &$data, &$sick, &$permit) {
                $newAttendance = $this->attendanceService->createAttendance($student_id, $scheduleWeek, 0, $sick, $permit, Carbon::now());
                $this->createPermit($request->file('evidence'), $today, $today, $request->description, $student_id, [$request->sw_id]);
                $data = $this->attendanceService->prepareAttendanceData($scheduleWeek, $newAttendance);
            });
            return new ApiResource(true, 'Success', $data);
        } catch (Exception $e) {
            return WebResponseUtils::base(null, 'Failed to do attendance permit', 500);
        }
    }

    public function createPermit($image, $start_date, $end_date, $description, $student_id, array $sw_ids)
    {

        try {
            DB::transaction(
                function () use ($image, $start_date, $end_date, $description, $student_id, $sw_ids) {
                    $cloudinaryImage = cloudinary()->upload($image, [
                        'folder' => 'evidence',
                        'transformation' => [
                            'quality' => 'auto:low',
                            'fetch_format' => 'auto'
                        ]
                    ]);
                    $permit = DB::table('permits')->insertGetId([
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'type_permit' => 'izin',
                        'description' => $description,
                        'evidence' => $cloudinaryImage->getSecurePath(),
                        'image_public_id' => $cloudinaryImage->getPublicId(),
                        'student_id' => $student_id
                    ]);

                    foreach ($sw_ids as $sw_id) {
                        DB::table('permit_details')->insert([
                            'permit_id' => $permit,
                            'schedule_week_id' => (int)$sw_id
                        ]);
                    }
                }
            );
        } catch (Exception $e) {
            return WebResponseUtils::base(null, 'Failed to crate permit', 500);
        }
    }

    function permitBeforeSchedule(PermitBeforeSchedule $request)
    {
        try {
            $student_id = Auth::id();
            $scheduleWeeks = [];
            $data = [];

            // Decode JSON string to array
            $sw_ids = json_decode($request->sw_id, true);

            if (!is_array($sw_ids)) {
                throw new Exception("Invalid schedule ID format.");
            }

            foreach ($sw_ids as $id) {
                $scheduleWeek = $this->scheduleService->getScheduleById($id);

                if ($scheduleWeek) {
                    $scheduleWeeks[] = $scheduleWeek;
                }
            }

            if (empty($scheduleWeeks)) {
                throw new Exception("No schedules found for today.");
            }

            DB::transaction(function () use ($request, $student_id, $sw_ids, &$data, &$scheduleWeeks) {
                foreach ($scheduleWeeks as $scheduleWeek) {
                    $sick = 0;
                    $permit = 0;

                    if ($request->permit_type === 'sakit') {
                        $sick = $scheduleWeek->time;
                    } elseif ($request->permit_type === 'izin') {
                        $permit = $scheduleWeek->time;
                    }

                    $newAttendance = $this->attendanceService->createAttendance($student_id, $scheduleWeek, 0, $sick, $permit, Carbon::now());
                    $data[] = $this->attendanceService->prepareAttendanceData($scheduleWeek, $newAttendance);
                }
                $this->createPermit($request->file('evidence'), $request->start_date, $request->end_date, $request->description, $student_id, $sw_ids);
            });

            return new ApiResource(true, 'Success', $data);
        } catch (Exception $e) {
            return WebResponseUtils::base($e->getMessage(), 'Failed to do attendance permit before permit', 500);
        }
    }

    function getPermitHistory(Request $request)
    {
        try {
            $student_id = Auth::id();
            $permitHistory = DB::table('permits as p')
                ->join('permit_details as pd', 'p.id', 'pd.permit_id')
                ->join('schedule_weeks as sw', 'pd.schedule_week_id', '=', 'sw.id')
                ->join('schedules as s', 'sw.schedule_id', '=', 's.id')
                ->leftJoin('attendances as a', 'sw.id', 'a.id')
                ->join('rooms as r', 's.room_id', '=', 'r.id')
                ->join('lecturers as l', 's.lecturer_id', '=', 'l.id')
                ->join('courses as c', 's.course_id', '=', 'c.id')
                ->join('weeks as w', 'sw.week_id', '=', 'w.id')
                ->select('p.*', 'pd.*', 'sw.*', 's.*', 'r.*', 'a.*', 'a.id as attendance_id', 'sw.id as sw_id', 'p.id as permit_id', 'p.start_date as permit_start', 'p.end_date as permit_end', 'pd.status as permit_status', 'pd.id as pd_id', 'r.name as room_name', 'l.name as lecturer_name', 'c.*', 'c.name as course_name', 'w.*')
                ->where('p.student_id', $student_id)
                ->where('pd.status', 'proses')
                ->orderBy('sw.date', 'asc')
                ->get();
            $result = $permitHistory->map(function ($item) {
                return [
                    "id" => $item->permit_id,
                    "status" => $item->permit_status,
                    "created_at" => Carbon::parse($item->created_at)->format('Y-m-d'),
                    "updated_at" => Carbon::parse($item->updated_at)->format('Y-m-d'),
                    "permit" => [
                        "id" => $item->permit_id,
                        "start_date" => $item->permit_start,
                        "end_date" => $item->permit_start,
                        "type_permit" => $item->type_permit,
                        "description" => $item->description,
                        "evidences" => $item->evidence
                    ],
                    "schedule_week" => [
                        "id" => $item->sw_id,
                        "date" => $item->date,
                        "is_online" => (bool)$item->is_online,
                        "status" => $item->status,
                        "opened_at" => $item->opened_at,
                        "closed_at" => $item->closed_at,
                        "schedule" => [
                            "id" => $item->schedule_id,
                            "day" => $item->day,
                            "start_time" => Carbon::parse($item->start_time)->format('H:i'),
                            "end_time" => Carbon::parse($item->end_time)->format('H:i'),
                            "week" => [
                                "id" => $item->week_id,
                                "name" => $item->name,
                                "start_date" => $item->start_date,
                                "end_date" => $item->end_date,
                            ],
                            "room" => [
                                "id" => $item->room_id,
                                "name" => $item->room_name,
                                "floor" => $item->floor,
                                "latitude" => $item->latitude,
                                "longitude" => $item->longitude,
                            ],
                            "lecturer" => [
                                "id" => $item->lecturer_id,
                                "name" => $item->lecturer_name,
                            ],
                            "course" => [
                                "id" => $item->course_id,
                                "name" => $item->course_name,
                                "sks" => $item->sks,
                                "time" => $item->time,
                            ],
                        ],
                        "attendance" =>  [
                            "id" => $item->attendance_id,
                            "sakit" => $item->sakit,
                            "izin" => $item->izin,
                            "alpha" => $item->alpha,
                            "entry_time" => Carbon::parse($item->entry_time)->format('H:i:s'),
                            "is_changed" => (bool)$item->is_changed,
                            "lecturer_verified" => (bool) $item->lecturer_verified,
                        ],
                    ]
                ];
            });
            return new ApiResource(true, 'Success', $result);
        } catch (Exception $e) {
            return WebResponseUtils::base(null, 'Failed to retrieve permit history', 500);
        }
    }

    public function permitAfter(PermitAfterRequest $request)
    {
        try {
            // $request->file('evidence'), $today, $today, $request->description, $student_id, [$request->sw_id]
            $student_id = Auth::id();
            $sick = 0;
            $permit = 0;
            $attendance = DB::table('attendances as a')
            ->where('id', '=', $request->attendance_id)
            ->where('a.student_id', $student_id)
            ->where('a.is_changed', false)
            ->first();

            if (!$attendance) {
                throw new Exception("Cannot do permit");
            }

            $scheduleWeek = $this->scheduleService->getScheduleById($attendance->schedule_week_id);
            if (!$scheduleWeek) {
                throw new Exception("No schedules found");
            }
            if ($request->permit_type === 'sakit') {
                $sick = $scheduleWeek->time;
            } elseif ($request->permit_type === 'izin') {
                $permit = $scheduleWeek->time;
            }


            $updatedAttendance = null;

            DB::transaction(function () use ($request, $scheduleWeek, $student_id, $sick, $permit, &$updatedAttendance) {
                $cloudinaryImage = cloudinary()->upload($request->file('evidence'), [
                    'folder' => 'evidence',
                    'transformation' => [
                        'quality' => 'auto:low',
                        'fetch_format' => 'auto'
                    ]
                ]);
                $permit_id = DB::table('permits')->insertGetId([
                    'start_date' => $scheduleWeek->date,
                    'end_date' => $scheduleWeek->date,
                    'type_permit' => $request->permit_type,
                    'description' => $request->description,
                    'evidence' => $cloudinaryImage->getSecurePath(),
                    'image_public_id' => $cloudinaryImage->getPublicId(),
                    'student_id' => $student_id
                ]);

                DB::table('permit_details')->insert([
                    'permit_id' => $permit_id,
                    'schedule_week_id' => $scheduleWeek->sw_id
                ]);

                DB::table('attendances as a')
                    ->where('id', '=', $request->attendance_id)
                    ->where('a.student_id', $student_id)
                    ->update([
                        'alpha' => 0,
                        'izin' => $permit,
                        'sakit' => $sick,
                    ]);

                $updatedAttendance = DB::table('attendances as a')
                    ->where('id', '=', $request->attendance_id)
                    ->where('a.student_id', $student_id)
                    ->first();
            });
            $data = $this->attendanceService->prepareAttendanceData($scheduleWeek, $updatedAttendance);
            return new ApiResource(true, 'Success', $data);
        } catch (Exception $e) {
        return WebResponseUtils::base($e, 'Failed to do attendance permit', 500);
        }
    }
}
