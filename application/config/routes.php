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

// User Management
$route['api/users']['get'] = 'api/auth/get_users';
$route['api/users']['options'] = 'api/auth/options';
$route['api/user']['get'] = 'api/auth/get_user';
$route['api/user']['put'] = 'api/auth/update_user';
$route['api/user']['post'] = 'api/auth/update_user';
$route['api/user']['delete'] = 'api/auth/delete_user';
$route['api/user']['options'] = 'api/auth/options';

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

// Student APIs
$route['api/student/grades']['get'] = 'studentcontroller/get_my_grades';

// Admin User Management
$route['api/change-status']['post'] = 'api/auth/change_user_status';

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

// Teacher Assigned Subjects and Sections
$route['api/teacher/assigned-subjects']['get'] = 'api/TeacherController/assigned_subjects_get';
$route['api/teacher/available-subjects']['get'] = 'api/TeacherController/available_subjects_get';
$route['api/teacher/available-sections/(:num)']['get'] = 'api/TeacherController/available_sections_get/$1';

$route['pdf/(:any)'] = 'pdf/serve/$1';

// Upload routes
$route['api/upload/profile'] = 'api/Upload/profile';
$route['api/upload/cover'] = 'api/Upload/cover';

// Image serving routes
$route['image/(:any)/(:any)'] = 'image/serve/$1/$2';

// Test route
$route['api/test/upload'] = 'api/Test/upload_test';


