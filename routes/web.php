<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\IcecashController;
use App\Http\Controllers\FullcoverController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ZinaraController;
use App\Http\Controllers\FaceValueController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ThirdController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DailyCollectionController;

use App\Http\Controllers\NetworkController;
use App\Exports\FaceValueExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupervisorFacevalueController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpinController;
use App\Models\Supervisorfacevalues;
use Psy\SuperglobalsEnv;
use App\Http\Controllers\CsvDataController;
use App\Http\Controllers\CashInHandBalanceController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\CourierSaleController;


Route::get('/ai/summarize-range', [AiController::class, 'summarizeDateRange'])->name('ai.summarize.range');

// AI Routes
Route::prefix('ai')->middleware(['auth'])->group(function () {
    Route::get('/summarize/daily/{collection}', [AiController::class, 'summarizeDailyCollection'])->name('ai.summarize.daily');
    Route::get('/', [AiController::class, 'index'])->name('ai.dashboard');
    Route::post('/summarize/range', [AiController::class, 'summarizeDateRange'])->name('ai.summarize.range');
    Route::get('/analyze/face-values', [AiController::class, 'analyzeAllocationTrends'])->name('ai.analyze.face-values');
    Route::get('/predict/exhaustion', [AiController::class, 'predictExhaustion'])->name('ai.predict.exhaustion');
    Route::get('/summarizer', [AiController::class, 'summarizer'])->name('ai.summarizer');
    Route::get('/trends', [AiController::class, 'trends'])->name('ai.trends');
    Route::get('/prediction', [AiController::class, 'prediction'])->name('ai.prediction');
});

Route::get('/spin', [SpinController::class, 'index'])->name('spin.index');

Route::get('/csv-data', [CsvDataController::class, 'index'])->name('csv-data.index');
Route::get('/csv-data/create', [CsvDataController::class, 'create'])->name('csv-data.create');
Route::post('/csv-data', [CsvDataController::class, 'store'])->name('csv-data.store');
Route::get('/csv-data/search', [CsvDataController::class, 'search'])->name('csv-data.search');

Route::resource('roles', RoleController::class);

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('/');
Route::get('/teams/getsites/{networkId}', [App\Http\Controllers\TeamsController::class, 'sitelist'])->name('getsites');

Route::resource('transactions', TransactionController::class);
Route::resource('supervisorfacevalues', SupervisorFacevalueController::class);
Route::get('allocate/{id}', [SupervisorFacevalueController::class, 'allocate'])->name('allocate');
Route::post('allocation', [SupervisorFacevalueController::class, 'allocation'])->name('allocation');

Auth::routes();

Route::resource('networks', NetworkController::class);
Route::post('/destroynetwork', [NetworkController::class, 'destroy'])->name('destroynetwork');


Route::resource('teams', TeamsController::class);
Route::get('/teams/resetpwd/{id}', [TeamsController::class, 'resetpwd'])->name('teams.resetpwd');
Route::get('/block', [NetworkController::class, 'block'])->name('block');


//facevaluelist
Route::get('/face-values/reports', [FaceValueController::class, 'reportsHub'])->name('facevalues.reports.hub');
Route::get('/face-values/reports/stock', [FaceValueController::class, 'supervisorStockReport'])->name('facevalues.reports.stock');
Route::get('/face-values/reports/exceptions', [FaceValueController::class, 'supervisorExceptionReport'])->name('facevalues.reports.exceptions');
Route::get('/face-values/reports/trace', [FaceValueController::class, 'traceReport'])->name('facevalues.reports.trace');
Route::get('/clientfvreport', [FaceValueController::class, 'clientfvreport'])->name('clientfvreport');
Route::post('/clientfvreport', [FaceValueController::class, 'clientfvreport'])->name('clientfvreport');

Route::get('/cumulativefvreport', [FaceValueController::class, 'cumulativefvreport'])->name('cumulativefvreport');
Route::post('/cumulativefvreport', [FaceValueController::class, 'cumulativefvreport'])->name('cumulativefvreport');


Route::get('/compiledhistory', [FaceValueController::class, 'compiledhistory'])->name('compiledhistory');


//facevaluelist compiledhistory
Route::get('/compiledhistory', [FaceValueController::class, 'compiledhistory'])->name('compiledhistory');

Route::post('/declare', [FaceValueController::class, 'declare'])->name('declare');
Route::get('/facevaluelist', [FaceValueController::class, 'facevaluelist'])->name('facevaluelist');
Route::get('/courier-sales', [CourierSaleController::class, 'index'])->name('courier.sales.index');
Route::post('/courier-sales', [CourierSaleController::class, 'store'])->name('courier.sales.store');
Route::get('/face-values/history', [FaceValueController::class, 'history'])->name('facevalues.history');
Route::get('/face-values/export', function () {
    return Excel::download(new FaceValueExport, 'face_value_history.xlsx');
})->name('facevalues.export');

// Route to display the edit form for a specific FaceValue record
Route::get('/facevalues/{facevalue}/edit', [FaceValueController::class, 'edit'])->name('facevalues.edit');

// Route to handle the submission of the updated FaceValue record
Route::put('/facevalues/{facevalue}', [FaceValueController::class, 'update'])->name('facevalues.update');


Route::get('/face-values/create', [FaceValueController::class, 'create'])->name('getcashvalues');
Route::post('/face-values', [FaceValueController::class, 'store'])->name('postcash');
Route::get('/face-values/history', [FaceValueController::class, 'history'])->name('gethistory');

// Index Routes
// Index Routes
Route::get('/home',[IndexController::class,'index'])->name('home');
Route::get('/collectionreports',[IndexController::class,'collectionreports'])->name('collectionreports');
Route::post('/collectionreports',[IndexController::class,'collectionreports'])->name('collectionreports');
//getSites

Route::get('/cumulativeNetworkReport',[ReportsController::class,'cumulativeNetworkReport'])->name('cumulativeNetworkReport');
Route::post('/cumulativeNetworkReport',[ReportsController::class,'cumulativeNetworkReport'])->name('cumulativeNetworkReport');

Route::get('/bysitesreports',[IndexController::class,'bysitesreports'])->name('bysitesreports');
Route::post('/bysitesreports',[IndexController::class,'bysitesreports'])->name('bysitesreports');
Route::get('/getsites/{data}',[IndexController::class,'getsites'])->name('reports.sites');
Route::get('/reports',[ReportsController::class,'index'])->name('reports.hub');
Route::get('/reports/applications',[ReportsController::class,'applicationReports'])->name('reports.applications');

Route::get('/icecash/create',[IcecashController::class,'index'])->name('geticecash');
Route::get('/icecash/manage',[IcecashController::class,'edit'])->name('editicecash');
Route::post('/icecash/post',[IcecashController::class,'store'])->name('posticecash');
Route::get('/icecash/reports',[IcecashController::class,'reports'])->name('geticereport');
Route::post('/icecash/reports/submit',[IcecashController::class,'search'])->name('posticereport');
Route::get('/icecash/pdf',[IcecashController::class,'downloadPDF'])->name('geticepdf');
Route::get('/icecash/results',[IcecashController::class,'results'])->name('geticeresults');


Route::get('/fullcover/create',[FullcoverController::class,'index'])->name('getfullcover');
Route::post('/fullcover/post',[FullcoverController::class,'store'])->name('postfullcover');

//Route::get('/fullcover/create',[FullcoverController::class,'index'])->name('getfullcover');
Route::get('/fullcover/manage',[FullcoverController::class,'edit'])->name('editfullcover');

//Zinara Routes
Route::get('/zinara/index',[ZinaraController::class,'index'])->name('getzinara');

//Site Routes
Route::get('/sites',[SiteController::class,'index'])->name('sites');
Route::get('/site/create',[SiteController::class,'create'])->name('getsite');
Route::get('/site/show',[SiteController::class,'show'])->name('showsite');
Route::get('/site/{site}/editsite',[SiteController::class,'edit'])->name('editsite');
Route::post('/site/destroy',[SiteController::class,'destroy'])->name('destroysite');
Route::post('/site/create/post',[SiteController::class,'store'])->name('postsite');
Route::post('/site/update/post',[SiteController::class,'update'])->name('updatesite');
Route::get('/site/manage',[SiteController::class,'edit'])->name('getsitemanage');


//Reset Password Routes
Route::get('/reset',[LoginController::class,'passchange'])->name('reset');
Route::post('/password/change', [LoginController::class, 'reset'])->name('passchange');

//Route::get('/form', [LoginController::class, 'form'])->name('logout');


//Third Routes
Route::get('/third/create',[ThirdController::class,'index'])->name('getthirdparty');
Route::post('/third/manage',[ThirdController::class,'store'])->name('postthirdparty');


Route::get('/collection',[DailyCollectionController::class,'create'])->name('collection');
Route::post('/collection/post',[DailyCollectionController::class,'store'])->name('postcollection');
Route::get('/collection/manage',[DailyCollectionController::class,'manage'])->name('getmanagesheet');
Route::get('/transactions',[DailyCollectionController::class,'transactions'])->name('gettransactions');


// NEW: User Reports Routes
Route::get('/reports/users', [DailyCollectionController::class, 'userReports'])->name('user.reports');
Route::post('/reports/users', [DailyCollectionController::class, 'userReports'])->name('user.reports.filter');

Route::get('/construction',[SiteController::class,'construction'])->name('construction');

// Budget Module Routes
Route::get('/budgets/charts/usd', [BudgetController::class, 'usdChart'])->name('budgets.charts.usd');
Route::get('/budgets/charts/zwg', [BudgetController::class, 'zwgChart'])->name('budgets.charts.zwg');
Route::resource('budgets', BudgetController::class);


Route::get('/face-values/allusers', [FaceValueController::class, 'allusers'])->name('facevalues.allusers');
Route::get('/face-values/getuser/{userid}', [FaceValueController::class, 'getuser'])->name('facevalues.getuser');
Route::post('/face-values/recalculate', [FaceValueController::class, 'recalculate'])->name('facevalues.recalculate');


Route::get('/dailycollection/ammendments', [DailyCollectionController::class, 'ammendments'])->name('dailycollection.ammendments');
Route::post('dailycollection/ammendmentrequest', [DailyCollectionController::class, 'ammendmentrequest'])->name('dailycollection.ammendmentrequest');
Route::get('dailycollection/ammendmentrequestlist', [DailyCollectionController::class, 'ammendmentrequestlist'])->name('dailycollection.ammendmentrequestlist');
Route::get('dailycollection/viewammendment/{collectionid}', [DailyCollectionController::class, 'viewammendment'])->name('dailycollection.viewammendment');
Route::get('dailycollection/viewrequest/{id}', [DailyCollectionController::class, 'viewrequest'])->name('dailycollection.viewrequest');
Route::post('dailycollection/approveammendmentrequest', [DailyCollectionController::class, 'approveammendmentrequest'])->name('dailycollection.approveammendmentrequest');

//cash in hand balances routes
Route::resource('cash-in-hand-balances', CashInHandBalanceController::class);
