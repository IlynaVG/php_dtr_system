<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="Description" content="Attendance Monitoring System">
    <meta name="Keywords" content="Attendance, Monitoring, System, HTML, PHP, CSS">
    <meta name="Author" content="Jaymar Gabriel S. Banking">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS | Attendance Monitoring System</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="asset/css/style.css">
    <script src="asset/js/bootstrap.min.js"></script>
    <style>
        /* Uniform button styling */
        .linkButton {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 60px;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            background-color: rgba(83, 14, 143, 1);
            color: #ffffff !important;
            border: 2px solid rgba(83, 14, 143, 1);
            transition: all 0.3s ease;
        }

        .headWall p {
            font-size: 20px;
            font-weight: 500;
        }

        /* Hover effect */
        .linkButton:hover {
            background-color: #ffffff;
            color: rgba(83, 14, 143, 1) !important;
            text-decoration: none;
            border: 2px solid rgba(83, 14, 143, 1);
        }

        .button-wrapper {
            margin-bottom: 15px;
        }
        
        .headWall {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="jumbotron text-center headWall">
        <h1>Attendance Monitoring System</h1>
        <p>The system is on the development process.</p>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <!-- 1. UPLOAD -->
            <div class="col-sm-4 button-wrapper">
                <a href="view/uploadPage.php" class="linkButton">UPLOAD</a>
            </div>

            <!-- 2. VIEW -->
            <div class="col-sm-4 button-wrapper">
                <a href="view/viewEmployee.php" class="linkButton">VIEW</a>
            </div>

            <!-- 3. BLANK FORM -->
            <div class="col-sm-4 button-wrapper">
                <a href="view/generateBlank.php" class="linkButton">BLANK FORM</a>
            </div>
        </div>
    </div>

    <footer class="footer text-center" style="margin-top: 50px;">
        <p>&copy; AMS | Chriz x Erricca</p>
    </footer>
</body>
</html>