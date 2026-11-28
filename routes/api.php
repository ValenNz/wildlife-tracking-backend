<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AggregateReportController;
use App\Http\Controllers\Api\AiInsightController;
use App\Http\Controllers\Api\AiPredictionController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AnimalGeoZoneController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GeoZoneController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\IncidentReportController;
use App\Http\Controllers\Api\MidtransNotificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\StaffFaceController;
use App\Http\Controllers\Api\TicketCheckInController;
use App\Http\Controllers\Api\TourismAdminController;
use App\Http\Controllers\Api\TourismCatalogController;
use App\Http\Controllers\Api\TrackingDeviceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VisitorBookingController;
use App\Http\Controllers\Api\WeatherDataController;
use App\Http\Controllers\Api\WildlifeCatalogController;
use App\Http\Middleware\RestrictDeviceTokens;
use Illuminate\Support\Facades\Route;

$routes = function () {
    Route::middleware('throttle:20,1,auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1,register');
    Route::get('register/challenge', [AuthController::class, 'registerChallenge'])->middleware('throttle:30,1,register-challenge');
    Route::middleware('throttle:60,1,tourism-catalog')->prefix('tourism')->group(function () {
        Route::get('routes', [TourismCatalogController::class, 'routes']);
        Route::get('routes/{slug}', [TourismCatalogController::class, 'showRoute'])->where('slug', '[a-z0-9-]+');
        Route::get('routes/{slug}/safety', [TourismCatalogController::class, 'routeSafety'])->where('slug', '[a-z0-9-]+');
        Route::get('guides', [TourismCatalogController::class, 'guides']);
        Route::get('guides/{id}', [TourismCatalogController::class, 'showGuide']);
        Route::get('guides/{id}/photo', [TourismCatalogController::class, 'guidePhoto']);
        Route::get('schedule', [TourismCatalogController::class, 'schedule']);
        Route::get('prices', [TourismCatalogController::class, 'prices']);
        Route::post('quote', [TourismCatalogController::class, 'quote']);
        Route::get('payment-info', [TourismCatalogController::class, 'paymentInfo']);
    });
    Route::middleware('throttle:60,1,wildlife-catalog')->prefix('wildlife')->group(function () {
        Route::get('species', [WildlifeCatalogController::class, 'index']);
        Route::get('species/{id}', [WildlifeCatalogController::class, 'show'])->whereNumber('id');
        Route::get('species/{id}/model', [WildlifeCatalogController::class, 'model'])->whereNumber('id');
    });
    Route::post('payments/midtrans/notification', MidtransNotificationController::class)->middleware('throttle:120,1,midtrans');
    Route::post('payments/webhook', PaymentWebhookController::class)->middleware('throttle:120,1,payment-webhook');

    Route::middleware(['auth:sanctum', RestrictDeviceTokens::class, 'log.activity'])->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('users/me', [UserController::class, 'me']);
        Route::patch('me', [UserController::class, 'updateProfile']);
        Route::patch('users/me', [UserController::class, 'updateProfile']);
        Route::put('me/password', [UserController::class, 'changePassword']);
        Route::put('users/me/password', [UserController::class, 'changePassword']);

        Route::middleware('permission:view_data')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('dashboard/charts', [DashboardController::class, 'charts']);
        });

        Route::middleware('permission:manage_users')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::get('users/{id}', [UserController::class, 'show']);
            Route::put('users/{id}', [UserController::class, 'update']);
            Route::patch('users/{id}/status', [UserController::class, 'updateStatus']);
            Route::delete('users/{id}', [UserController::class, 'destroy']);
            Route::get('users/{id}/activity', [UserController::class, 'activity']);
            Route::get('users/{id}/tokens', [UserController::class, 'tokens']);
            Route::delete('users/{id}/tokens/{tokenId}', [UserController::class, 'revokeToken']);

            Route::get('staff-faces', [StaffFaceController::class, 'index']);
            Route::post('staff-faces', [StaffFaceController::class, 'store']);
            Route::delete('staff-faces', [StaffFaceController::class, 'destroyByName']);
            Route::get('staff-faces/{id}/file', [StaffFaceController::class, 'file']);
            Route::delete('staff-faces/{id}', [StaffFaceController::class, 'destroy']);

            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
        });

        Route::middleware('permission:manage_roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index']);
            Route::post('roles', [RoleController::class, 'store']);
            Route::get('roles/{id}', [RoleController::class, 'show']);
            Route::put('roles/{id}', [RoleController::class, 'update']);
            Route::delete('roles/{id}', [RoleController::class, 'destroy']);
            Route::get('roles/{id}/permissions', [RoleController::class, 'permissions']);
            Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
            Route::post('roles/{id}/permissions/{permissionId}', [RoleController::class, 'assignPermission']);
            Route::delete('roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);

            Route::get('permissions', [PermissionController::class, 'index']);
            Route::get('permissions/{id}', [PermissionController::class, 'show']);
        });

        Route::middleware('permission:view_data')->group(function () {
            Route::get('species', [SpeciesController::class, 'index']);
            Route::get('species/{id}', [SpeciesController::class, 'show']);
            Route::get('species/{id}/model', [SpeciesController::class, 'model'])->whereNumber('id');

            Route::get('animals', [AnimalController::class, 'index']);
            Route::get('animals/{id}', [AnimalController::class, 'show']);
            Route::get('animals/{animalId}/track', [AnimalController::class, 'track']);
            Route::get('animals/{animalId}/zone-visits', [AnimalController::class, 'zoneVisits']);
            Route::get('animals/{animalId}/incidents', [AnimalController::class, 'incidents']);
            Route::get('animals/{animalId}/ai-observations', [AnimalController::class, 'aiObservations']);
            Route::get('animals/{animalId}/reference-photos', [AnimalController::class, 'referencePhotos']);
            Route::get('animals/{animalId}/reference-photos/{embeddingId}/file', [AnimalController::class, 'referencePhotoFile']);

            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::get($prefix, [TrackingDeviceController::class, 'index']);
                Route::get("{$prefix}/{id}", [TrackingDeviceController::class, 'show']);
                Route::get("{$prefix}/{id}/latest-reading", [TrackingDeviceController::class, 'latestReading']);
                Route::get("{$prefix}/{id}/gps-readings", [TrackingDeviceController::class, 'gpsReadings']);
                Route::get("{$prefix}/{id}/battery-history", [TrackingDeviceController::class, 'batteryHistory']);
                Route::get("{$prefix}/{id}/captures", [TrackingDeviceController::class, 'captures']);
            }

            Route::get('gps-readings', [GpsReadingController::class, 'index']);
            Route::get('gps-readings/{id}', [GpsReadingController::class, 'show']);
            Route::get('gps-readings/{readingId}/weather', [WeatherDataController::class, 'byReading']);

            Route::get('geo-zones', [GeoZoneController::class, 'index']);
            Route::get('geo-zones/locate', [GeoZoneController::class, 'locate']);
            Route::get('geo-zones/{id}', [GeoZoneController::class, 'show']);
            Route::get('geo-zones/{id}/animals', [GeoZoneController::class, 'animals']);
            Route::get('geo-zones/{id}/visits', [GeoZoneController::class, 'visits']);
            Route::get('geo-zones/{id}/devices', [GeoZoneController::class, 'devices']);

            Route::get('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'index']);
            Route::get('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'show']);

            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::get($prefix, [IncidentReportController::class, 'index']);
                Route::get("{$prefix}/{id}", [IncidentReportController::class, 'show']);
                Route::get("{$prefix}/{id}/media", [IncidentReportController::class, 'media']);
            }

            Route::get('weather-data', [WeatherDataController::class, 'index']);
            Route::get('weather-data/{id}', [WeatherDataController::class, 'show']);
        });

        Route::middleware('permission:manage_master_data')->group(function () {
            Route::post('species', [SpeciesController::class, 'store']);
            Route::put('species/{id}', [SpeciesController::class, 'update']);
            Route::delete('species/{id}', [SpeciesController::class, 'destroy']);
            Route::post('species/{id}/model', [SpeciesController::class, 'uploadModel'])->middleware('throttle:10,1,species-model');
            Route::delete('species/{id}/model', [SpeciesController::class, 'deleteModel']);

            Route::post('animals', [AnimalController::class, 'store']);
            Route::put('animals/{id}', [AnimalController::class, 'update']);
            Route::patch('animals/{id}/status', [AnimalController::class, 'updateStatus']);
            Route::delete('animals/{id}', [AnimalController::class, 'destroy']);
            Route::post('animals/{animalId}/reference-photos', [AnimalController::class, 'storeReferencePhoto']);
            Route::delete('animals/{animalId}/reference-photos/{embeddingId}', [AnimalController::class, 'destroyReferencePhoto']);

            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::post($prefix, [TrackingDeviceController::class, 'store']);
                Route::put("{$prefix}/{id}", [TrackingDeviceController::class, 'update']);
                Route::delete("{$prefix}/{id}", [TrackingDeviceController::class, 'destroy']);
                Route::patch("{$prefix}/{id}/assign-animal", [TrackingDeviceController::class, 'assignAnimal']);
                Route::delete("{$prefix}/{id}/assign-animal", [TrackingDeviceController::class, 'unassignAnimal']);
                Route::get("{$prefix}/{id}/api-token", [DeviceTokenController::class, 'show']);
                Route::post("{$prefix}/{id}/api-token", [DeviceTokenController::class, 'store'])->middleware('throttle:10,1,device-token');
                Route::delete("{$prefix}/{id}/api-token", [DeviceTokenController::class, 'destroy']);
                Route::patch("{$prefix}/{id}/condition", [TrackingDeviceController::class, 'updateCondition']);
                Route::patch("{$prefix}/{id}/status", [TrackingDeviceController::class, 'updateStatus']);
            }

            Route::post('geo-zones', [GeoZoneController::class, 'store']);
            Route::put('geo-zones/{id}', [GeoZoneController::class, 'update']);
            Route::delete('geo-zones/{id}', [GeoZoneController::class, 'destroy']);
        });

        Route::middleware('permission:create_data')->group(function () {
            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::patch("{$prefix}/{id}/telemetry", [TrackingDeviceController::class, 'telemetry']);
                Route::post("{$prefix}/{id}/captures", [TrackingDeviceController::class, 'uploadCapture']);
            }

            Route::post('gps-readings', [GpsReadingController::class, 'store']);
            Route::post('gps-readings/bulk', [GpsReadingController::class, 'bulk']);

            Route::post('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'store']);

            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::post($prefix, [IncidentReportController::class, 'store']);
                Route::post("{$prefix}/{id}/media", [IncidentReportController::class, 'uploadMedia']);
            }

            Route::post('weather-data/ingest', [WeatherDataController::class, 'ingest']);
            Route::post('weather-data/sync', [WeatherDataController::class, 'sync']);
        });

        Route::middleware('permission:update_data')->group(function () {
            Route::patch('animal-geo-zone-visits/{id}/exit', [AnimalGeoZoneController::class, 'exit']);

            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::put("{$prefix}/{id}", [IncidentReportController::class, 'update']);
            }
        });

        Route::middleware('permission:delete_data')->group(function () {
            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::patch("{$prefix}/{id}/sync-status", [IncidentReportController::class, 'updateSyncStatus']);
            }
        });

        Route::middleware('permission:delete_data')->group(function () {
            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::delete("{$prefix}/{id}", [IncidentReportController::class, 'destroy']);
            }
            Route::delete('ai-predictions/{id}', [AiPredictionController::class, 'destroy']);
            Route::delete('media-uploads/{id}', [AiPredictionController::class, 'destroyMedia']);
        });

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications', [NotificationController::class, 'deleteRead']);
        Route::get('notifications/{id}', [NotificationController::class, 'show']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        Route::middleware('permission:view_data')->group(function () {
            Route::get('ai-predictions', [AiPredictionController::class, 'index']);
            Route::get('ai-predictions/pending-verification', [AiPredictionController::class, 'pendingVerification']);
            Route::get('ai-predictions/{id}', [AiPredictionController::class, 'show']);

            Route::get('media-uploads', [AiPredictionController::class, 'mediaUploads']);
            Route::get('media-uploads/{id}', [AiPredictionController::class, 'showMedia']);
            Route::get('media-uploads/{id}/file', [AiPredictionController::class, 'file']);
            Route::get('media-uploads/{mediaId}/predictions', [AiPredictionController::class, 'mediaPredictions']);

            Route::get('ai/summary-report', [AiInsightController::class, 'summaryReport']);
            Route::get('ai/recap', [AiInsightController::class, 'recap']);
            Route::get('ai/health', [AiInsightController::class, 'health']);
            Route::get('ai/threats', [AiInsightController::class, 'threats']);
            Route::get('ai/vocabulary', [AiInsightController::class, 'vocabulary']);
        });

        Route::middleware(['permission:upload_media', 'throttle:30,1,ai-upload'])->group(function () {
            Route::post('ai/upload', [AiPredictionController::class, 'upload']);
            Route::post('media-uploads', [AiPredictionController::class, 'upload']);
            Route::post('ai/detect-objects', [AiInsightController::class, 'detectObjects']);
            Route::post('media-uploads/{mediaId}/predictions', [AiPredictionController::class, 'triggerPrediction']);
        });

        Route::middleware('permission:verify_ai')->group(function () {
            Route::match(['post', 'patch'], 'ai-predictions/{id}/verify', [AiPredictionController::class, 'verify']);
            Route::post('ai-predictions/{id}/retry', [AiPredictionController::class, 'retry']);
        });

        Route::middleware('permission:view_reports')->group(function () {
            Route::get('aggregate-reports', [AggregateReportController::class, 'index']);
            Route::post('aggregate-reports/generate', [AggregateReportController::class, 'generate']);
            Route::get('aggregate-reports/{id}', [AggregateReportController::class, 'show']);
            Route::get('aggregate-reports/{id}/download', [AggregateReportController::class, 'download']);
        });
        Route::delete('aggregate-reports/{id}', [AggregateReportController::class, 'destroy'])->middleware('permission:delete_data');

        Route::middleware('permission:export_data')->prefix('export')->group(function () {
            Route::get('gps/csv', [ExportController::class, 'gpsCsv']);
            Route::get('gps/geojson', [ExportController::class, 'gpsGeoJson']);
            Route::get('animals/csv', [ExportController::class, 'animalsCsv']);
            Route::get('species/csv', [ExportController::class, 'speciesCsv']);
            Route::get('devices/csv', [ExportController::class, 'devicesCsv']);
            Route::get('incidents/csv', [ExportController::class, 'incidentsCsv']);
            Route::get('weather/csv', [ExportController::class, 'weatherCsv']);
            Route::get('ai-predictions/csv', [ExportController::class, 'aiPredictionsCsv']);
            Route::get('geo-zones/csv', [ExportController::class, 'zonesCsv']);
            Route::get('geo-zones/geojson', [ExportController::class, 'zonesGeoJson']);
            Route::get('zone-visits/csv', [ExportController::class, 'zoneVisitsCsv']);
            Route::get('aggregate-report/{id}', [ExportController::class, 'aggregateReport']);
        });
        Route::middleware('permission:manage_users')->prefix('export')->group(function () {
            Route::get('users/csv', [ExportController::class, 'usersCsv']);
            Route::get('activity-logs/csv', [ExportController::class, 'activityLogsCsv']);
        });

        Route::post('tourism/routes/{slug}/emergency-alert', [TourismCatalogController::class, 'sendEmergencyAlert'])
            ->where('slug', '[a-z0-9-]+')
            ->middleware('throttle:5,1,trail-emergency');
        Route::middleware('permission:book_tickets')->prefix('visitor')->group(function () {
            Route::get('bookings', [VisitorBookingController::class, 'index']);
            Route::post('bookings', [VisitorBookingController::class, 'store'])->middleware('throttle:10,1,visitor-booking');
            Route::get('bookings/{code}', [VisitorBookingController::class, 'show']);
            Route::post('bookings/{code}/cancel', [VisitorBookingController::class, 'cancel']);
            Route::post('bookings/{code}/payment-proof', [VisitorBookingController::class, 'uploadProof'])->middleware('throttle:10,1,visitor-proof');
            Route::get('bookings/{code}/ticket', [VisitorBookingController::class, 'ticket']);
            Route::post('bookings/{code}/pay-online', [VisitorBookingController::class, 'payOnline'])->middleware('throttle:10,1,visitor-pay-online');
            Route::post('bookings/{code}/simulate-payment', [VisitorBookingController::class, 'simulatePayment'])->middleware('throttle:20,1,visitor-simulate-payment');
            Route::post('bookings/{code}/review', [VisitorBookingController::class, 'review'])->middleware('throttle:10,1,visitor-review');
        });

        Route::middleware('permission:validate_tickets')->prefix('tourism/check-in')->group(function () {
            Route::post('/', [TicketCheckInController::class, 'checkIn'])->middleware('throttle:60,1,ticket-check-in');
            Route::get('today', [TicketCheckInController::class, 'today']);
        });

        Route::middleware('permission:sell_tickets')->prefix('pos')->group(function () {
            Route::get('session', [PosController::class, 'current']);
            Route::post('session/open', [PosController::class, 'open'])->middleware('throttle:10,1,pos-session');
            Route::post('session/close', [PosController::class, 'close'])->middleware('throttle:10,1,pos-session');
            Route::get('sessions', [PosController::class, 'sessions']);
            Route::get('sessions/{id}', [PosController::class, 'showSession'])->whereNumber('id');
            Route::get('sales', [PosController::class, 'sales']);
            Route::post('sales', [PosController::class, 'sell'])->middleware('throttle:60,1,pos-sale');
            Route::get('sales/{code}', [PosController::class, 'showSale']);
            Route::post('sales/{code}/void', [PosController::class, 'void'])->middleware('throttle:20,1,pos-void');
        });
        Route::middleware('permission:manage_tourism')->get('pos/report', [PosController::class, 'dailyReport']);

        Route::middleware('permission:manage_tourism')->prefix('tourism/admin')->group(function () {
            Route::get('routes', [TourismAdminController::class, 'routes']);
            Route::post('routes', [TourismAdminController::class, 'storeRoute']);
            Route::get('routes/{id}', [TourismAdminController::class, 'showRoute']);
            Route::put('routes/{id}', [TourismAdminController::class, 'updateRoute']);
            Route::delete('routes/{id}', [TourismAdminController::class, 'destroyRoute']);

            Route::get('guides', [TourismAdminController::class, 'guides']);
            Route::post('guides', [TourismAdminController::class, 'storeGuide']);
            Route::put('guides/{id}', [TourismAdminController::class, 'updateGuide']);
            Route::delete('guides/{id}', [TourismAdminController::class, 'destroyGuide']);
            Route::post('guides/{id}/photo', [TourismAdminController::class, 'uploadGuidePhoto']);

            Route::get('shifts', [TourismAdminController::class, 'shifts']);
            Route::post('shifts', [TourismAdminController::class, 'storeShift']);
            Route::put('shifts/{id}', [TourismAdminController::class, 'updateShift']);
            Route::delete('shifts/{id}', [TourismAdminController::class, 'destroyShift']);

            Route::get('prices', [TourismAdminController::class, 'prices']);
            Route::put('prices/{id}', [TourismAdminController::class, 'updatePrice']);
            Route::get('holidays', [TourismAdminController::class, 'holidays']);
            Route::post('holidays', [TourismAdminController::class, 'storeHoliday']);
            Route::delete('holidays/{id}', [TourismAdminController::class, 'destroyHoliday']);

            Route::get('bookings', [TourismAdminController::class, 'bookings']);
            Route::get('bookings/{code}', [TourismAdminController::class, 'showBooking']);
            Route::post('bookings/{code}/cancel', [TourismAdminController::class, 'cancelBooking']);

            Route::get('payments', [TourismAdminController::class, 'payments']);
            Route::get('payments/{id}/proof', [TourismAdminController::class, 'paymentProof']);
            Route::post('payments/{id}/verify', [TourismAdminController::class, 'verifyPayment']);

            Route::get('reviews', [TourismAdminController::class, 'reviews']);
            Route::patch('reviews/{id}', [TourismAdminController::class, 'updateReviewVisibility']);
        });
    });
};

$routes();
Route::prefix('v1')->group($routes);
