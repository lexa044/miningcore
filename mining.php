<?php

class MiningCore
{
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;

	public function __construct($username, $password, $host = 'localhost', $port = 4000, $url = null)
    {
        $this->username      = $username;
        $this->password      = $password;
        $this->host          = $host;
        $this->port          = $port;
        $this->url           = $url;
        // Set some defaults
        $this->proto         = 'http';
    }

    public function __call($method, $params)
    {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;
        // If no parameters are passed, this will be an empty array
        $params = array_values($params);
        // The ID should be unique for each call
        $this->id++;
        // Build the request, it's ok that params might have any empty array
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));
        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request
        );
        // This prevents users from getting the following warning when open_basedir is set:
        // Warning: curl_setopt() [function.curl-setopt]:
        //   CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        curl_setopt_array($curl, $options);
        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, true);
        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);
        curl_close($curl);
        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }
        if ($this->response['error']) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif ($this->status != 200) {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        if ($this->error) {
            return false;
        }
        return $this->response['result'];
    }
	
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	
	<title>Betchip Mining Pool (BTP)</title>
    <meta property="og:title" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support." />
    <meta name="twitter:card" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support.">
	<meta name="description" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support." />
	<meta name="og:description" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support." />
	<meta itemprop="description" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support.">
	<meta name="twitter:description" content="The profitable and reliable Betchip mining pool with competitive fees, server locations accross the globe and beginner-friendly support.">
	
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Fonts -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css">
    <link href="//fonts.googleapis.com/css?family=Raleway:700,500i|Roboto:300,400,700|Roboto+Mono" rel="stylesheet">
    <style type="text/css">
        body {
            color: #404041;
        }

        ul {
            list-style: none;
        }

        a:hover {
            text-decoration: none;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            -ms-transition: all 0.5s;
            -o-transition: all 0.5s;
            transition: all 0.5s;
        }

        a {
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            -ms-transition: all 0.5s;
            -o-transition: all 0.5s;
            transition: all 0.5s;
        }

        .logo {
            text-align: center;
            height: 60px;
            background-color: #d33c44;
            padding-top: 14px;
            padding-left: 0px;
            padding-right: 0px;
        }

        h1 {
            height: 60px;
            font-family: 'Raleway', sans-serif;
            font-style: italic;
            font-size: 18px;
            font-weight: 500;
            padding-top: 20px;
            color: #DEDED5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-color: #404041;
            text-align: center;
            line-height: 19px;
            margin: 0;
        }

        pre {
            white-space: pre-wrap; /* Since CSS 2.1 */
            white-space: -moz-pre-wrap; /* Mozilla, since 1999 */
            white-space: -o-pre-wrap; /* Opera 7 */
            word-wrap: break-word; /* Internet Explorer 5.5+ */
            height: 200px;
            background-color: #404041;
            color: #DEDED5;
            font-family: 'Roboto Mono', monospace;
            font-size: 11px;
        }

        p {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            margin: 0px;
            line-height: 20px;
        }

        h2 {
            font-family: 'Roboto', sans-serif;
            font-size: 27px;
            line-height: 43px;
            font-weight: 300;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: #0071BC;
        }

        .header {
            margin-bottom: 30px;
        }

        .text-success {
            color: #88C671;
        }

        .text-danger {
            color: #d33c44;
        }

        .congrats {
            color: #d33c44;
            font-family: 'Raleway', sans-serif;
            font-style: italic;
            font-size: 18px;
            font-weight: 500;
            padding-top: 20px;
        }

        .path {
            font-family: 'Roboto Mono', monospace;
            font-size: 10px;
        }

        .path::before {
            color: #DEDED5;
            content: "\f07c";
            display: inline-block;
            font-family: FontAwesome;
            width: 1.3em;
            font-size: 11px;
        }

        .cake-button {
            position: absolute;
            left: 50%;
            margin-top: 135px;
            margin-left: -108px;
            text-align: center;
            z-index: 2;
        }

        .cake-button span {
            font-size: 15px;
            font-family: "Raleway", sans-serif;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #DEDED5;
            background-color: #404041;
            border: 0;
            padding: 21px 20px 17px 20px;
            border-radius: 0 12px 12px 0;
            display: inline-block;
            vertical-align: top;
            height: 60px;
            margin: 0 0 0 -4px;
            box-shadow: 0px 7px 0px #353535;
        }

        .cake-button:hover {
            -webkit-filter: brightness(80%);
            filter: brightness(80%);
            transition: all .2s ease-in-out;
        }

        .cake-button:active span, .cake-button:active img {
            box-shadow: none;
            margin-top: 7px !important;
            margin-bottom: -7px !important;
        }

        .cake-button img {
            background-color: #d33c43;
            line-height: 30px;
            padding: 16px 16px 13px 17px;
            border-radius: 12px 0px 0px 12px;
            display: inline-block;
            vertical-align: top;
            height: 60px;
            box-shadow: 0px 7px 0px #AF333C;
        }

        .mixer-button {
            position: absolute;
            left: 50%;
            margin-top: 235px;
            margin-left: -72px;
            text-align: center;
            z-index: 2;
        }

        .mixer-button span {
            font-size: 12px;
            font-family: "Raleway", sans-serif;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #DEDED5;
            background-color: #404041;
            border: 0;
            padding: 11px 10px 4px 10px;
            border-radius: 0 6px 6px 0;
            display: inline-block;
            vertical-align: top;
            height: 36px;
            margin: 0 0 0 -4px;
            box-shadow: 0px 5px 0px #353535;
        }

        .mixer-button:hover {
            -webkit-filter: brightness(80%);
            filter: brightness(80%);
            transition: all .2s ease-in-out;
        }

        .mixer-button:active span, .mixer-button:active img {
            box-shadow: none;
            margin-top: 5px !important;
            margin-bottom: -5px !important;
        }

        .mixer-button img {
            background-color: #d33c43;
            line-height: 36px;
            padding: 11px 10px 11px 10px;
            border-radius: 6px 0px 0px 6px;
            display: inline-block;
            vertical-align: top;
            height: 36px;
            box-shadow: 0px 5px 0px #AF333C;
        }

        .starting {
            font-family: "Raleway", sans-serif;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
        }

        #start img {
            transition: all 0.75s 0.25s;
            transform: rotate(0);
        }

        #start.rotate img {
            transform: rotate(90deg);
        }

        .starting a {
            color: #404041;
            line-height: 1.8em;
        }

        .starting a:hover {
            opacity: 0.5;
        }

        .checklist {
            list-style-type: none;
            padding-left: 28px;
        }

        .checklist i.fa {
            margin-right: 3px;
        }

        .checklist span.text-danger {
            font-weight: bold;
        }

        #progress {
            position: relative;
            text-align: center;
            padding-top: 35px;
            padding-bottom: 35px;
        }
        #progress.finished img {
            opacity: 0.5;
        }
        #progress img { display: none; }
        #progress.progress-1 img.p1 { display: inline; }
        #progress.progress-2 img.p2 { display: inline; }
        #progress.progress-3 img.p3 { display: inline; }
        #progress.progress-4 img.p4 { display: inline; }
        #progress.progress-5 img.p5 { display: inline; }
        #progress.progress-6 img.p6 { display: inline; }
        #progress.progress-7 img.p7 { display: inline; }
        #progress.progress-8 img.p8 { display: inline; }
        #progress.progress-9 img.p9 { display: inline; transition: all .3s ease .3s; }

        #composer_path {
            width: 175px;
            display: inline-block;
        }

        .btn {
            border-radius: 0;
            background: #d33c44;
            border-color: #a01f26;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active  {
            background: #c82e36 !important;
            border-color: #8c1b21 !important;;
        }

        .input-group-addon {
            border-radius: 0;
            border-color: #deded5;
        }

        .form-control {
            border-color: #deded5;
            border-radius: 0;
            -webkit-box-shadow: none;
            -moz-box-shadow: none;
            box-shadow: none;
        }

        .radio {
            margin-top: 3px;
            margin-bottom: 3px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        fieldset {
            margin-bottom: 10px;
        }

        legend {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-color: #deded5;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .form-group label, .radio label {
            margin-bottom: 3px;
            font-size: 12px;
            text-transform: uppercase;
        }

        legend label {
            margin: 0;
        }

        .radio label {
            text-transform: none;
        }

        input[type=radio] {
            margin-top: 2px;
        }
        input[type=checkbox] {
            margin-top: 4px;
        }

        @media (min-width: 1200px) {
            .container {
                width: 970px;
            }
        }

        @media (min-width: 768px) {
            .header {
                padding-left: 30px;
                padding-right: 30px;
            }
            .hide-me {
                display: none;
            }
            .cake-button {
                margin-left: -159px;
            }
            .starting {
                margin-top: 120px;
            }
        }

        @media (max-width: 767px) {
            h1 {
                font-size: 14px;
                padding: 12px;
                line-height: 18px;
                height: auto;
            }

            .cake-button span {
                padding: 15px 20px 17px 20px;
                line-height: 17px;
            }
        }
	/*ChartJS - Mozilla*/
	canvas{
		-moz-user-select: none;
		-webkit-user-select: none;
		-ms-user-select: none;
	}
	

/* Tabs panel */
.tabbable-panel {
  /*border:1px solid #eee;*/
  padding: 10px;
}

/* Default mode */
.tabbable-line > .nav-tabs {
  border: none;
  margin: 0px;
}
.tabbable-line > .nav-tabs > li {
  margin-right: 2px;
}
.tabbable-line > .nav-tabs > li > a {
  border: 0;
  margin-right: 0;
  color: #737373;
}
.tabbable-line > .nav-tabs > li > a > i {
  color: #a6a6a6;
}
.tabbable-line > .nav-tabs > li.open, .tabbable-line > .nav-tabs > li:hover {
  border-bottom: 4px solid #fbcdcf;
}
.tabbable-line > .nav-tabs > li.open > a, .tabbable-line > .nav-tabs > li:hover > a {
  border: 0;
  background: none !important;
  color: #333333;
}
.tabbable-line > .nav-tabs > li.open > a > i, .tabbable-line > .nav-tabs > li:hover > a > i {
  color: #a6a6a6;
}
.tabbable-line > .nav-tabs > li.open .dropdown-menu, .tabbable-line > .nav-tabs > li:hover .dropdown-menu {
  margin-top: 0px;
}
.tabbable-line > .nav-tabs > li.active {
  border-bottom: 4px solid #f3565d;
  position: relative;
}
.tabbable-line > .nav-tabs > li.active > a {
  border: 0;
  color: #333333;
}
.tabbable-line > .nav-tabs > li.active > a > i {
  color: #404040;
}
.tabbable-line > .tab-content {
  margin-top: -3px;
  background-color: #fff;
  border: 0;
  border-top: 1px solid #eee;
  padding: 15px 0;
}
.portlet .tabbable-line > .tab-content {
  padding-bottom: 0;
}

/* Below tabs mode */

.tabbable-line.tabs-below > .nav-tabs > li {
  border-top: 4px solid transparent;
}
.tabbable-line.tabs-below > .nav-tabs > li > a {
  margin-top: 0;
}
.tabbable-line.tabs-below > .nav-tabs > li:hover {
  border-bottom: 0;
  border-top: 4px solid #fbcdcf;
}
.tabbable-line.tabs-below > .nav-tabs > li.active {
  margin-bottom: -2px;
  border-bottom: 0;
  border-top: 4px solid #f3565d;
}
.tabbable-line.tabs-below > .tab-content {
  margin-top: -10px;
  border-top: 0;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
}
	
    </style>
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-4 logo">
				<img src="<?php echo $svgs['logo'] ?>"/>
			</div>
			<div class="col-md-8" style="padding-left:0; padding-right:0"><h1>Welcome to Betchip Mining Pool.</h1></div>
		</div>
	</div>
	  
	  <div class="container">
		<h2>Betchip Mining Pool (BTP) - SHA256</h2>
		<hr/>
		<div class="row">
			<div class="col-lg-12 col-sm-12">
				<div class="card">
					<div class="header">
						<h4 class="title">Hash Rate</h4>
					</div>
					<div class="content">
						<div id="chartStatsHashRate" style="width:98%;">
							<canvas id="chart"></canvas>
						</div>
					</div>
				</div>			
			</div>
		</div>
		<div class="row">
			<div class="col-lg-8 col-sm-8">
			
			<div class="tabbable-panel">
				<div class="tabbable-line">
					<ul class="nav nav-tabs ">
						<li class="active">
							<a href="#tab_default_1" data-toggle="tab">Getting Started</a>
						</li>
						<li>
							<a href="#tab_default_2" data-toggle="tab">Blocks</a>
						</li>
						<li>
							<a href="#tab_default_3" data-toggle="tab">Payments</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="tab_default_1">
							<p>
								I'm in Tab 1.
							</p>
							<p>
								Duis autem eum iriure dolor in hendrerit in vulputate velit esse molestie consequat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat.
							</p>
							<p>
								<a class="btn btn-success" href="http://j.mp/metronictheme" target="_blank">
									Learn more...
								</a>
							</p>
						</div>
						<div class="tab-pane" id="tab_default_2">
							<p>
								Howdy, I'm in Tab 2.
							</p>
							<p>
								Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat. Ut wisi enim ad minim veniam, quis nostrud exerci tation.
							</p>
							<p>
								<a class="btn btn-warning" href="http://j.mp/metronictheme" target="_blank">
									Click for more features...
								</a>
							</p>
						</div>
						<div class="tab-pane" id="tab_default_3">
							<p>
								Howdy, I'm in Tab 3.
							</p>
							<p>
								Duis autem vel eum iriure dolor in hendrerit in vulputate. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat
							</p>
							<p>
								<a class="btn btn-info" href="http://j.mp/metronictheme" target="_blank">
									Learn more...
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
			</div>
			<div class="col-lg-4 col-sm-4">
			</div>
		</div>
	  </div>
	
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
	<script>
var options = {
        scales: {
            yAxes: [{
                stacked: true
            }]
        }
    };	
var data = {
        labels: ["Green", "Green", "Green", "Green", "Green", "Green"],
        datasets: [{
            label: 'Pool Hashrate in TH/s (Last 24h)',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
                'rgba(75, 192, 192, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(75, 192, 192, 0.2)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    };
var ctx = document.getElementById("chart");
var myChart = new Chart(ctx, {
    type: 'line',
    data: data,
    options: options
});
</script>
  </body>
</html>