<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Success!</title>
    <link href="{{ URL::asset('styles.css') }}" rel="stylesheet" type="text/css">
    <style>
        button {
            background-color: #3274d6;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            height: 40px;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: #2868c7;
        }

        #notificationContainer {
            display: inline-block;
            vertical-align: top;
            margin-left: 10px;
        }

        #copyNotification {
            background-color: #4caf50;
            color: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
<nav>
    <div class="heading">
        <h4>Onelink-CSR</h4>
    </div>
    <ul class="nav-links">
        <li><a class="active" href="{{ url('/') }}">Home</a></li>
    </ul>
</nav>
<div class="uploadsuccess">
    <h1>Upload Success!</h1>
    <form>
        @csrf
        <div>
            <a href="https://rt.transperfect.com/Ticket/Display.html?id={{ preg_replace('/\D+$/', '', $cert_number) }}">RT ticket</a><br><br>
            <label><b>CSR Configuration (If this is incorrect, please reach out to DevOps): </b></label>
            <table>
                <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($confArray as $key => $value)
                    @if ((strpos($key, 'domains') !== 0))
                        <tr>
                            <td>{{ ucfirst($key) }}</td>
                            <td>{{ $value }}</td>
                        </tr>
                    @else
                        @foreach ($value as $index => $item)
                            <tr>
                                <td>Domain {{ $index + 1 }}</td>
                                <td>{{ $item }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <div>
            <label><b>CSR:</b></label>
            <pre id="csrContent">{{ $csrContent }}</pre>
            <button type="button" id="copyButton">Copy CSR</button>
            <div id="notificationContainer">
                <div id="copyNotification">CSR copied to clipboard</div>
            </div>
        </div>
    </form>
</div>
<script>
    function copyToClipboard() {
        const csrContent = document.getElementById('csrContent').textContent;
        navigator.clipboard.writeText(csrContent).then(() => {
            const notification = document.getElementById('copyNotification');
            notification.style.opacity = 1;
            setTimeout(() => {
                notification.style.opacity = 0;
            }, 2000); // Display for 2 seconds
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }



    document.getElementById('copyButton').addEventListener('click', copyToClipboard);
</script>
</body>
</html>
