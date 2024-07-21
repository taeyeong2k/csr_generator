<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Error</title>
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

        #copyNotification, #copyPkeyNotification {
            background-color: #4caf50;
            color: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        strong {
            color: red
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
<div class="uploadfailure">
    <h1>Error Uploading to Vault</h1>
    <form>
    @csrf
        <div>
            <h2><strong>There was an error uploading the private key to vault.</strong> Error message: {{ $errorMessage }}</h2>
            <h3>The private key and CSR were generated correctly. <strong>Please add the private key to Vault manually using the ticket number!</strong> You can still send the CSR to the client.</h3>
            <a href="https://rt.transperfect.com/Ticket/Display.html?id={{ preg_replace('/\D+$/', '', $cert_number) }}">RT ticket</a><br><br>
            <label><b>CSR Configuration (If this is incorrect, you can regenerate the correct CSR as nothing was added to Vault): </b></label>
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
        <div>
            <label><b>Private Key:</b></label>
            <pre id="privateKey">{{ $privateKey }}</pre>
            <button type="button" id="copyPkeyButton">Copy Private Key</button>
            <div id="notificationContainer">
                <div id="copyPkeyNotification">Private Key copied to clipboard</div>
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
    function copyPkeyToClipboard() {
        const pkeyContent = document.getElementById('privateKey').textContent;
        navigator.clipboard.writeText(pkeyContent).then(() => {
            const notification = document.getElementById('copyPkeyNotification');
            notification.style.opacity = 1;
            setTimeout(() => {
                notification.style.opacity = 0;
            }, 2000); // Display for 2 seconds
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }

    document.getElementById('copyButton').addEventListener('click', copyToClipboard);
    document.getElementById('copyPkeyButton').addEventListener('click', copyPkeyToClipboard);

</script>
</body>
</html>
