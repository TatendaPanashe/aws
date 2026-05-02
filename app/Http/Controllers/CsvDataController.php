<?php

namespace App\Http\Controllers;

use App\Models\CsvData;
use App\Http\Requests\StoreCsvDataRequest;
use App\Http\Requests\UpdateCsvDataRequest;
use Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CsvDataController extends Controller
{
    public function index()
    {
        $csvData = CsvData::all();
        return view('csv-data.index', compact('csvData'));
    }

    public function create()
    {
        return view('csv-data.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt',
        ]);

        $filePath = $request->file('csv_file')->getRealPath();

        $csvData = array();
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($i > 0) {
                    $csvData[] = [
                        'id_number' => $data[0],
                        'approved' => $data[1],
                        'agent' => $data[2],
                        'classification' => $data[3],
                        'main_agent' => $data[4],
                        'issue_date' => $data[5],
                        'status' => $data[6],
                        'customer_name' => $data[7],
                        'policy_no' => $data[8],
                        'insurance_type' => $data[9],
                        'end_date' => $data[10],
                        'vehicle_reg_no' => $data[11],
                        'location' => $data[12], 
                        'amount'=> $data[13],
                    ];
                }
                $i++;
            }
            fclose($handle);
        }

        //dd($csvData);

        CsvData::insert($csvData);

        return redirect()->route('csv-data.index')->with('success', 'CSV data imported successfully.');
    }

    public function search(Request $request)
{
    $query = CsvData::query();

    if ($request->has('agent')) {
        $query->where('agent', 'like', '%' . $request->input('agent') . '%');
    }

    if ($request->has('location')) {
        $query->where('location', 'like', '%' . $request->input('location') . '%');
    }

    if ($request->has('start_date') && $request->has('end_date')) {
        $fromDate = $request->input('start_date');
        $toDate = $request->input('end_date');

        $query->whereBetween('start_date', [$fromDate, $toDate]);
    }elseif($request->has('start_date')){
        $query->where('start_date','>=',$request->input('start_date'));
    }elseif($request->has('end_date')){
        $query->where('end_date','<=',$request->input('end_date'));
    }


    $results = $query->get();// Paginate results for better performance
   // dd($results);
    return view('csv-data.results', compact('results'));
}
}

  