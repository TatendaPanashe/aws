



<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Spin & Win</title>
        
        <!-- Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600&display=swap" rel="stylesheet" />

        <!-- Style CSS -->
        <link rel="stylesheet" href="{{asset('assets/spin/style.css')}}">
    </head>

    <body>
        <div class="wrapper">
            <h1> GRUMA Spin the Wheel </h1>
            <div class="container">
                <canvas id="wheel"></canvas>
                <button id="spin-btn">Spin</button>
                <img src="{{asset('assets/spin/spinner-arrow-.svg')}}" alt="spinner-arrow" />
            </div>

            <div id="final-value">
                <p>Click On The Spin Button To Start</p>
            </div>
        </div>

        <!-- Chart JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

        <!-- Chart JS Plugin for displaying text over chart -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.1.0/chartjs-plugin-datalabels.min.js"></script>

        <!-- Script Js -->
        <script src="{{asset('assets/spin/script.js')}}"></script>
    </body>
</html>
