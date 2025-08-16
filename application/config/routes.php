<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth
$route['api/login']['post'] = 'api/auth/login';
$route['api/register']['post'] = 'api/auth/register';
$route['api/test']['post'] = 'api/auth/test_password';
$route['api/refresh-token']['post'] = 'api/auth/refresh_token';
$route['api/validate-token']['get'] = 'api/auth/validate_token';
$route['api/logout']['post'] = 'api/auth/logout';
$route['api/auth/google']['post'] = 'api/auth/google_oauth';

// Test endpoints
$route['api/test']['get'] = 'api/testcontroller/test';
$route['api/test/headers']['get'] = 'api/testcontroller/headers';

// User Management
$route['api/users']['get'] = 'api/auth/get_users';
$route['api/users']['options'] = 'api/auth/options';
$route['api/user']['get'] = 'api/auth/get_user';
$route['api/user']['put'] = 'api/auth/update_user';
$route['api/user']['post'] = 'api/auth/update_user';
$route['api/user']['delete'] = 'api/auth/delete_user';
$route['api/user']['options'] = 'api/auth/options';
$route['api/user/me']['get'] = 'api/auth/get_current_user';

// Specific Update Routes
$route['api/admin/update']['put'] = 'api/auth/update_user';
$route['api/admin/update']['post'] = 'api/auth/update_user';
$route['api/admin/update']['options'] = 'api/auth/options';
$route['api/teacher/update']['put'] = 'api/auth/update_user';
$route['api/teacher/update']['post'] = 'api/auth/update_user';
$route['api/teacher/update']['options'] = 'api/auth/options';
$route['api/student/update']['put'] = 'api/auth/update_user';
$route['api/student/update']['post'] = 'api/auth/update_user';
$route['api/student/update']['options'] = 'api/auth/options';

// Specific Delete Routes
$route['api/admin/delete']['delete'] = 'api/auth/delete_user';
$route['api/admin/delete']['options'] = 'api/auth/options';
$route['api/teacher/delete']['delete'] = 'api/auth/delete_user';
$route['api/teacher/delete']['options'] = 'api/auth/options';
$route['api/student/delete']['delete'] = 'api/auth/delete_user';
$route['api/student/delete']['options'] = 'api/auth/options';

// Admin APIs
$route['api/admin/users/create']['post'] = 'admincontroller/create_user';
$route['api/admin/sections']['get'] = 'api/AdminController/sections_get';
$route['api/admin/sections']['post'] = 'api/AdminController/sections_post';
$route['api/admin/sections/(:num)']['get'] = 'api/AdminController/section_get/$1';
$route['api/admin/sections/(:num)']['put'] = 'api/AdminController/sections_put/$1';
$route['api/admin/sections/(:num)']['delete'] = 'api/AdminController/sections_delete/$1';
$route['api/admin/sections/(:num)/students']['get'] = 'api/AdminController/section_students_get/$1';
$route['api/admin/sections/(:num)/assign-students']['post'] = 'api/AdminController/assign_students_post/$1';
$route['api/admin/sections/(:num)/remove-students']['post'] = 'api/AdminController/remove_students_post/$1';
$route['api/admin/sections/year/(:any)']['get'] = 'api/AdminController/sections_by_year_get/$1';
$route['api/admin/sections/year']['get'] = 'api/AdminController/sections_by_year_get';
$route['api/admin/sections/semester/(:any)/year/(:any)']['get'] = 'api/AdminController/sections_by_semester_year_get/$1/$2';
$route['api/admin/sections/semester/(:any)']['get'] = 'api/AdminController/sections_by_semester_year_get/$1';
$route['api/admin/sections/semester']['get'] = 'api/AdminController/sections_by_semester_year_get';
$route['api/admin/sections/debug']['get'] = 'api/AdminController/sections_debug_get';
$route['api/admin/advisers']['get'] = 'api/AdminController/advisers_get';
$route['api/admin/programs']['get'] = 'api/AdminController/programs_get';
$route['api/admin/year-levels']['get'] = 'api/AdminController/year_levels_get';
$route['api/admin/semesters']['get'] = 'api/AdminController/semesters_get';
$route['api/admin/academic-years']['get'] = 'api/AdminController/academic_years_get';
$route['api/admin/students/available']['get'] = 'api/AdminController/available_students_get';
$route['api/admin/students']['get'] = 'api/AdminController/all_students_get';

// Teacher APIs
$route['api/teacher/attendance']['post'] = 'teachercontroller/mark_attendance_qr';

// Attendance Management
$route['api/attendance/classes']['get'] = 'api/AttendanceController/classes_get';
$route['api/attendance/students/(:num)/(:any)']['get'] = 'api/AttendanceController/students_get/$1/$2';
$route['api/attendance/students/(:num)']['get'] = 'api/AttendanceController/students_get/$1';
$route['api/attendance/record']['post'] = 'api/AttendanceController/record_post';
$route['api/attendance/bulk-record']['post'] = 'api/AttendanceController/bulk_record_post';
$route['api/attendance/records/(:num)/(:any)']['get'] = 'api/AttendanceController/records_get/$1/$2';
$route['api/attendance/update/(:num)']['put'] = 'api/AttendanceController/update_put/$1';
$route['api/attendance/summary/(:num)']['get'] = 'api/AttendanceController/summary_get/$1';
$route['api/attendance/export/(:num)']['get'] = 'api/AttendanceController/export_get/$1';
$route['api/attendance/delete/(:num)']['delete'] = 'api/AttendanceController/delete_delete/$1';
$route['api/attendance/all']['get'] = 'api/AttendanceController/all_get';
$route['api/attendance/teacher-assignments']['get'] = 'api/AttendanceController/teacher_assignments_get';
$route['api/attendance/student']['get'] = 'api/AttendanceController/student_get';
$route['api/attendance/sync-excuse-letters']['post'] = 'api/AttendanceController/sync_excuse_letters_post';
$route['api/attendance/excuse-letter-status/(:any)/(:any)']['get'] = 'api/AttendanceController/excuse_letter_status_get/$1/$2';

// Student APIs
$route['api/student/grades']['get'] = 'api/StudentController/grades_get';
$route['api/student/join-class']['post'] = 'api/StudentController/join_class';
$route['api/student/my-classes']['get'] = 'api/StudentController/my_classes';
$route['api/student/leave-class']['delete'] = 'api/StudentController/leave_class';
$route['api/student/debug-classes']['get'] = 'api/StudentController/debug_classes';
$route['api/student/classroom/(:any)/people']['get'] = 'api/StudentController/classroom_people_get/$1';
$route['api/student/classroom/(:any)/stream']['get'] = 'api/StudentController/classroom_stream_get/$1';
$route['api/student/classroom/(:any)/stream']['post'] = 'api/StudentController/classroom_stream_post/$1';

// Student Stream Comments
$route['api/student/classroom/(:any)/stream/(:num)/comment']['post'] = 'api/StudentController/classroom_stream_comment_post/$1/$2';
$route['api/student/classroom/(:any)/stream/(:num)/comments']['get'] = 'api/StudentController/classroom_stream_comments_get/$1/$2';
$route['api/student/classroom/(:any)/stream/(:num)/comment/(:num)']['put'] = 'api/StudentController/classroom_stream_comment_put/$1/$2/$3';
$route['api/student/classroom/(:any)/stream/(:num)/comment/(:num)']['delete'] = 'api/StudentController/classroom_stream_comment_delete/$1/$2/$3';

// Excuse Letter Management
$route['api/excuse-letters/submit']['post'] = 'api/ExcuseLetterController/submit_post';
$route['api/excuse-letters/student']['get'] = 'api/ExcuseLetterController/student_get';
$route['api/excuse-letters/teacher']['get'] = 'api/ExcuseLetterController/teacher_get';
$route['api/excuse-letters/update/(:num)']['put'] = 'api/ExcuseLetterController/update_put/$1';
$route['api/excuse-letters/delete/(:num)']['delete'] = 'api/ExcuseLetterController/delete_delete/$1';
$route['api/excuse-letters/statistics']['get'] = 'api/ExcuseLetterController/statistics_get';

// Admin User Management
$route['api/change-status']['post'] = 'api/auth/change_user_status';

// Admin Audit Log Management
$route['api/admin/audit-logs']['get'] = 'api/AdminController/audit_logs_get';
$route['api/admin/audit-logs/(:num)']['get'] = 'api/AdminController/audit_log_get/$1';
$route['api/admin/audit-logs/modules']['get'] = 'api/AdminController/audit_logs_modules_get';
$route['api/admin/audit-logs/roles']['get'] = 'api/AdminController/audit_logs_roles_get';
$route['api/admin/audit-logs/export']['get'] = 'api/AdminController/audit_logs_export_get';

// Role-specific Audit Log Endpoints
$route['api/admin/audit-logs/admin']['get'] = 'api/AdminController/audit_logs_admin_get';
$route['api/admin/audit-logs/teacher']['get'] = 'api/AdminController/audit_logs_teacher_get';
$route['api/admin/audit-logs/student']['get'] = 'api/AdminController/audit_logs_student_get';

// Catch-all OPTIONS route for all /api/* endpoints
$route['api/(:any)']['options'] = 'api/auth/options';

// For shortcut and full program name via URL segment
$route['api/admin/sections_by_program/(:any)']['get'] = 'api/AdminController/sections_by_program_get/$1';
// For query string (optional, but recommended for clarity)
$route['api/admin/sections_by_program']['get'] = 'api/AdminController/sections_by_program_get';

// New route for sections grouped by year level for a program
$route['api/admin/sections_by_program_year/(:any)']['get'] = 'api/AdminController/sections_by_program_year_get/$1';
$route['api/admin/sections_by_program_year']['get'] = 'api/AdminController/sections_by_program_year_get';

// New route for sections by program and specific year level
$route['api/admin/sections_by_program_year_specific']['get'] = 'api/AdminController/sections_by_program_year_specific_get';

// Admin Classes (Subject Offerings) Management
$route['api/admin/classes']['get'] = 'api/AdminController/classes_get';
$route['api/admin/classes']['post'] = 'api/AdminController/classes_post';
$route['api/admin/classes/(:num)']['get'] = 'api/AdminController/class_get/$1';
$route['api/admin/classes/(:num)']['put'] = 'api/AdminController/classes_put/$1';
$route['api/admin/classes/(:num)']['delete'] = 'api/AdminController/classes_delete/$1';

// Admin Subject Management
$route['api/admin/subjects']['get'] = 'api/AdminController/subjects_get';
$route['api/admin/subjects']['post'] = 'api/AdminController/subjects_post';
$route['api/admin/subjects/(:num)']['put'] = 'api/AdminController/subjects_put/$1';
$route['api/admin/subjects/(:num)']['delete'] = 'api/AdminController/subjects_delete/$1';

// Teacher Classroom Management
$route['api/teacher/classrooms']['get'] = 'api/TeacherController/classrooms_get';
$route['api/teacher/classrooms']['post'] = 'api/TeacherController/classrooms_post';
$route['api/teacher/classrooms/(:num)']['get'] = 'api/TeacherController/classroom_get/$1';
$route['api/teacher/classrooms/(:num)']['put'] = 'api/TeacherController/classrooms_put/$1';
$route['api/teacher/classrooms/(:num)']['delete'] = 'api/TeacherController/classrooms_delete/$1';
$route['api/teacher/classroom/(:any)']['get'] = 'api/TeacherController/classroom_by_code_get/$1';
$route['api/teacher/classroom/(:any)']['put'] = 'api/TeacherController/classrooms_put_by_code/$1';
$route['api/teacher/classroom/(:any)']['delete'] = 'api/TeacherController/classrooms_delete_by_code/$1';
$route['api/teacher/classroom/(:any)/stream']['post'] = 'api/TeacherController/classroom_stream_post/$1';
$route['api/teacher/classroom/(:any)/stream']['get'] = 'api/TeacherController/classroom_stream_get/$1';
$route['api/teacher/classroom/(:any)/stream/(:num)/like']['post'] = 'api/TeacherController/classroom_stream_like_post/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/unlike']['post'] = 'api/TeacherController/classroom_stream_unlike_post/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/pin']['post'] = 'api/TeacherController/classroom_stream_pin_post/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/unpin']['post'] = 'api/TeacherController/classroom_stream_unpin_post/$1/$2';
$route['api/teacher/classroom/(:any)/stream/scheduled']['get'] = 'api/TeacherController/classroom_stream_scheduled_get/$1';
$route['api/teacher/classroom/(:any)/stream/drafts']['get'] = 'api/TeacherController/classroom_stream_drafts_get/$1';
$route['api/teacher/classroom/(:any)/stream/draft/(:num)']['put'] = 'api/TeacherController/classroom_stream_draft_put/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/comment']['post'] = 'api/TeacherController/classroom_stream_comment_post/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/comments']['get'] = 'api/TeacherController/classroom_stream_comments_get/$1/$2';
$route['api/teacher/classroom/(:any)/stream/(:num)/comment/(:num)']['put'] = 'api/TeacherController/classroom_stream_comment_put/$1/$2/$3';
$route['api/teacher/classroom/(:any)/stream/(:num)/comment/(:num)']['delete'] = 'api/TeacherController/classroom_stream_comment_delete/$1/$2/$3';

// Teacher Classroom Student Management
$route['api/teacher/classroom/(:any)/students']['get'] = 'api/TeacherController/classroom_students_get/$1';
$route['api/teacher/classroom/(:any)/enrollment-stats']['get'] = 'api/TeacherController/classroom_enrollment_stats_get/$1';
$route['api/teacher/classroom/(:any)/grades']['get'] = 'api/TeacherController/classroom_grades_get/$1';
$route['api/teacher/classroom/(:any)/comprehensive-grades']['get'] = 'api/TeacherController/classroom_comprehensive_grades_get/$1';
$route['api/teacher/classroom/(:any)/export-grades']['get'] = 'api/TeacherController/classroom_export_grades_get/$1';

// Task Management
$route['api/tasks/create']['post'] = 'api/TaskController/create_post';
$route['api/tasks/teacher']['get'] = 'api/TaskController/teacher_get';
$route['api/tasks/student']['get'] = 'api/TaskController/student_get';
$route['api/tasks/student/assigned']['get'] = 'api/TaskController/student_assigned_get';
$route['api/tasks/student/(:num)']['get'] = 'api/TaskController/student_task_get/$1';
$route['api/tasks/student/(:num)/attachments']['get'] = 'api/TaskController/student_task_attachments_get/$1';
$route['api/tasks/(:num)']['get'] = 'api/TaskController/task_get/$1';
$route['api/tasks/(:num)']['put'] = 'api/TaskController/task_put/$1';
$route['api/tasks/(:num)']['delete'] = 'api/TaskController/task_delete/$1';
$route['api/tasks/(:num)/hard-delete']['delete'] = 'api/TaskController/hard_delete_task/$1';
$route['api/tasks/(:num)/publish']['post'] = 'api/TaskController/publish_post/$1';
$route['api/tasks/(:num)/schedule']['post'] = 'api/TaskController/schedule_post/$1';
$route['api/tasks/(:num)/archive']['post'] = 'api/TaskController/archive_post/$1';
$route['api/tasks/(:num)/submit']['post'] = 'api/TaskController/submit_post/$1';
$route['api/tasks/(:num)/comments']['get'] = 'api/TaskController/comments_get/$1';
$route['api/tasks/(:num)/comments']['post'] = 'api/TaskController/comment_post/$1';
$route['api/tasks/submissions/(:num)/grade']['post'] = 'api/TaskController/grade_submission_post/$1';
$route['api/tasks/submissions/(:num)']['get'] = 'api/TaskController/submission_get/$1';
$route['api/tasks/submissions/(:num)/attachments/(:num)']['delete'] = 'api/TaskController/delete_attachment_delete/$1/$2';
$route['api/tasks/(:num)/submission']['get'] = 'api/TaskController/student_submission_get/$1';
$route['api/tasks/(:num)/submissions']['get'] = 'api/TaskController/task_submissions_get/$1';
$route['api/tasks/(:num)/stats']['get'] = 'api/TaskController/stats_get/$1';
$route['api/tasks/(:num)/bulk-grade']['post'] = 'api/TaskController/bulk_grade_post/$1';
$route['api/tasks/files/(:any)']['get'] = 'api/TaskController/serve_file/$1';
$route['api/tasks/files/info/(:any)']['get'] = 'api/TaskController/get_task_file_info/$1';
$route['api/tasks/files/list']['get'] = 'api/TaskController/list_task_files';
$route['api/tasks/submissions/files/(:any)']['get'] = 'api/TaskController/serve_submission_file/$1';
$route['api/tasks/(:num)/preview']['get'] = 'api/TaskController/preview_file/$1';
$route['api/tasks/test-upload']['get'] = 'api/TaskController/test_upload_get';
$route['api/tasks/debug-assignments']['get'] = 'api/TaskController/debug_assignments_get';
$route['api/tasks/available-students']['get'] = 'api/TaskController/available_students_get';
$route['api/tasks/(:num)/assigned-students']['get'] = 'api/TaskController/assigned_students_get/$1';
$route['api/tasks/(:num)/assign-students']['post'] = 'api/TaskController/assign_students_post/$1';
$route['api/tasks/(:num)/assignment-stats']['get'] = 'api/TaskController/assignment_stats_get/$1';
$route['api/tasks/(:num)/attachments']['get'] = 'api/TaskController/task_attachments_get/$1';

// Teacher Assigned Subjects and Sections
$route['api/teacher/assigned-subjects']['get'] = 'api/TeacherController/assigned_subjects_get';
$route['api/teacher/available-subjects']['get'] = 'api/TeacherController/available_subjects_get';
$route['api/teacher/available-sections/(:num)']['get'] = 'api/TeacherController/available_sections_get/$1';

// Attendance Logs API
$route['api/attendance-logs/logs']['get'] = 'api/AttendanceLogController/logs_get';
$route['api/attendance-logs/log/(:num)']['get'] = 'api/AttendanceLogController/log_get/$1';
$route['api/attendance-logs/export']['get'] = 'api/AttendanceLogController/export_get';
$route['api/attendance-logs/statistics']['get'] = 'api/AttendanceLogController/statistics_get';
$route['api/attendance-logs/filters']['get'] = 'api/AttendanceLogController/filters_get';

$route['pdf/(:any)'] = 'pdf/serve/$1';

// Upload routes
$route['api/upload/profile'] = 'api/Upload/profile';
$route['api/upload/cover'] = 'api/Upload/cover';

// File serving routes
$route['image/(:any)/(:any)'] = 'image/serve/$1/$2';
$route['file/(:any)/(:any)'] = 'file/serve/$1/$2';

// Test route
$route['api/test/upload'] = 'api/Test/upload_test';

// Notification Management
$route['api/notifications']['get'] = 'api/NotificationController/get_notifications';
$route['api/notifications/(:num)/read']['put'] = 'api/NotificationController/mark_as_read/$1';
$route['api/notifications/mark-all-read']['put'] = 'api/NotificationController/mark_all_as_read';
$route['api/notifications/(:num)']['delete'] = 'api/NotificationController/delete_notification/$1';
$route['api/notifications/settings']['get'] = 'api/NotificationController/get_settings';
$route['api/notifications/settings']['put'] = 'api/NotificationController/update_settings';
$route['api/notifications/unread-count']['get'] = 'api/NotificationController/get_unread_count';
$route['api/notifications/recent']['get'] = 'api/NotificationController/get_recent';
$route['api/notifications/urgent']['get'] = 'api/NotificationController/get_urgent';

// Real-time Notification Stream (SSE)
$route['api/notifications/stream']['get'] = 'api/NotificationStreamController/stream';
$route['api/notifications/status']['get'] = 'api/NotificationStreamController/status';
// Token as URI segment, routed to simple Notifications controller per request
$route['api/notifications/stream/(:any)']['get'] = 'api/Notifications/stream/$1';

// QR Grading API - Perfect for face-to-face classroom activities
$route['api/qr-grading/quick-grade']['post'] = 'api/QRGradingController/quick_grade_post';
$route['api/qr-grading/bulk-quick-grade']['post'] = 'api/QRGradingController/bulk_quick_grade_post';
$route['api/qr-grading/student-qr/(:num)']['get'] = 'api/QRGradingController/student_qr_get/$1';
$route['api/qr-grading/class-qr/(:any)']['get'] = 'api/QRGradingController/class_qr_get/$1';
