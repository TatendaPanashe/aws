

<!DOCTYPE html>
<html>
<head>
    <title>Report Results</title>
</head>
<body>
    <h1>Report Results</h1>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction Type</th>
                <th>Amount</th>
                </tr>
        </thead>
        <tbody>
            @foreach ($reports as $report)
                <tr>
                    <td>{{ $report->date }}</td>
                    <td>{{ $report->transaction_type }}</td>
                    <td>{{ $report->amount }}</td>
                    </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('reports.downloadPDF', $request->all()) }}">Download PDF</a>
</body>
</html>

@endsection