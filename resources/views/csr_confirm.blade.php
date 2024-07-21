<!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Confirm CSR</title>
    <link href="{{ URL::asset('styles.css') }}" rel="stylesheet" type="text/css">
    <style>
        .prefix-wrapper {
            position: relative;
        }
        .prefix {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: rgba(0, 0, 0, 0.5); /* Transparent color */
            pointer-events: none;
        }
        #cert_number {
            padding-left: 60px;
        }
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
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-full-width {
            grid-column: 1 / -1;
        }

        .name-inputs {
            grid-column: 1 / -1;
            display: flex;
            gap: 20px;
            width: 150%
        }

        .first_name, .last_name {
            flex: 1;
        }
        .domain-comparison {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .domain-diff {
            margin-top: 10px;
        }
        .domain-diff ul {
            list-style-type: none;
            padding-left: 0;
        }
        .domain-diff li {
            margin-bottom: 5px;
        }
        .added {
            color: green;
        }
        .removed {
            color: red;
        }
        .warning {
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
<div class="csrconfirm">
    <h1>Confirm CSR Contents</h1>
    @if(isset($cert_error))
        <div class="alert alert-warning">
            <strong class="warning">Warning:</strong> Existing cert check failed. {{ $cert_error }}.
            If this is a new certificate for a new domain, this is OK. If this is a renewal for an existing domain,
            please double check the domain has been entered correctly.
        </div>
    @endif
    <form action="{{ route('generate.final.csr') }}" method="POST">
        @csrf
        <div class="grid-container">
            @if(isset($existing_cert) && is_array($existing_cert) && !empty($existing_cert))
                <div class="existing-cert">
                    <h2 class="text-xl font-semibold mb-2">Checked Existing Certificate: {{ $existing_cert["checked_domain"] ?? 'N/A'}}</h2>
                    <div>
                        <p><strong>Common Name:</strong> {{ $existing_cert['common_name'] ?? 'N/A' }}</p>
                        <p><strong>Valid Until:</strong> {{ $existing_cert['valid_to'] ?? 'N/A' }}</p>
                        <details>
                            <summary>Show full details</summary>
                            <div>
                                <p><strong>Domains:</strong></p>
                                <ul>
                                    @if(isset($existing_cert['domains']) && is_array($existing_cert['domains']))
                                        @foreach($existing_cert['domains'] as $domain)
                                            <li>{{ $domain }}</li>
                                        @endforeach
                                    @else
                                        <li>N/A</li>
                                    @endif
                                </ul>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="domain-comparison">
                    <h2>Domain Comparison</h2>
                    <div id="domainDiff" class="domain-diff">
                        <!-- Domain differences will be displayed here -->
                    </div>
                    <button type="button" id="matchDomains" style="display: none;">Match Existing Domains</button>
                </div>
            @endif

    <div class="mb-4">
        <h2>CSR Configuration</h2>
        <table>
            <thead>
            <tr>
                <th class="px-4 py-2">Field</th>
                <th class="px-4 py-2">Value</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($confArray as $key => $value)
                @if ((strpos($key, 'Domain') !== 0) && (strpos($key, 'Common') !== 0))
                    <tr>
                        <td>{{ $key }}</td>
                        <td>
                            @if (is_array($value))
                                @foreach ($value as $index => $item)
                                    <input type="text" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                                <button type="button" class="add-field" data-field="{{ $key }}">Add</button>
                            @else
                                @if ($key === 'State')
                                    <input type="text" name="{{ $key }}" value="{{ $value }}" minlength="3" pattern="^[A-Za-z\s]{3,}$" title="Please enter the full state name (minimum 3 characters, no abbreviations)" required>
                                    <span class="error-message" style="color: red; display: none;">Please enter the full state name (minimum 3 characters, no abbreviations)</span>
                                @else
                                    <input type="text" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
        <div class="mb-4">
            <h2>Domains</h2>
            <table class="w-full" id="domainsTable">
                <thead>
                <tr>
                    <th class="px-4 py-2">Domain</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($confArray as $key => $value)
                    @if (strpos($key, 'Domain') === 0)
                        @foreach ($value as $index => $item)
                        <tr>
                            <td class="border px-4 py-2">
                                <input type="text" name="domains[]" value="{{ $item }}" class="w-full">
                            </td>
                            <td class="border px-4 py-2">
                                <button type="button" class="removeDomain">Remove</button>
                            </td>
                        </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>
            <button type="button" id="addDomain">Add Domain</button>
            <button type="button" id="resetChanges">Reset Changes</button>
        </div>
            <div class="mb-4 grid-full-width">
                <label for="cert_number"><b>CERT Number</b></label>
                <div class="prefix-wrapper">
                    <span class="prefix">CERT-</span>
                    <label for="cert_number">Create a new RT ticket and enter the ticket number here. Save the CSR and send it to the client. </label>
                    <input type="text" id="cert_number" name="cert_number" pattern="^\d{1,6}(-[a-zA-Z\d]*)?$" title="Must match the format <number> or <number>-<optional alphanumeric> e.g. 123456, 123456A, or 123456-XYZ" required>                </div>
            </div>
            <div class="grid-container">
                <div class="grid-full-width">
                    <label for="generated_by"><b>Generated by:</b></label>
                </div>
                <div class="name-inputs">
                    <div class="name">
                        <label for="name">Your Full Name/TP Username</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                </div>
            </div>
    </div>
        <div>
            <label for="key_size" style="display: block;">
                <input type="checkbox" id="key_size" name="key_size">
                Use 4096 bit RSA key (default = 2048)
            </label>
            <button type="submit" style="display: block; margin-top: 10px;">
                Generate CSR and Upload Private Key
            </button>
        </div>
    </form>
</div>
<script>
document.getElementById('addDomain').addEventListener('click', function() {
    const domainsTable = document.getElementById('domainsTable').getElementsByTagName('tbody')[0];
    const newRow = domainsTable.insertRow();
    const domainCount = domainsTable.rows.length;
    newRow.innerHTML = `
        <td class="border px-4 py-2">
            <input type="text" name="domains[]" class="w-full" placeholder="Enter domain">
        </td>
        <td class="border px-4 py-2">
            <button type="button" class="removeDomain">Remove</button>
        </td>
    `;
    compareDomains()
});

// Use event delegation to handle the remove button clicks
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('removeDomain')) {
        const domainsTable = document.getElementById('domainsTable').getElementsByTagName('tbody')[0];
        const rowCount = domainsTable.rows.length;
        if (rowCount > 1) {
        e.target.closest('tr').remove();
        compareDomains();
    } else {
        alert('There must be at least one domain.');
        }
    }
});

// Function to compare domains
function compareDomains() {
    const existingDomains = @json($existing_cert['domains'] ?? []);
    const currentDomains = Array.from(document.querySelectorAll('input[name="domains[]"]')).map(input => input.value);

    const added = currentDomains.filter(domain => !existingDomains.includes(domain));
    const removed = existingDomains.filter(domain => !currentDomains.includes(domain));

    const diffElement = document.getElementById('domainDiff');
    const matchButton = document.getElementById('matchDomains');

    if (added.length > 0 || removed.length > 0) {
    let diffHtml = '<ul>';
    added.forEach(domain => diffHtml += `<li class="added">+ ${domain}</li>`);
    removed.forEach(domain => diffHtml += `<li class="removed">- ${domain}</li>`);
    diffHtml += '</ul>';

    diffElement.innerHTML = diffHtml;
    matchButton.style.display = 'block';
} else {
    diffElement.innerHTML = '<p>No differences found.</p>';
    matchButton.style.display = 'none';
    }
}

// Function to match domains
function matchDomains() {
    const existingDomains = @json($existing_cert['domains'] ?? []);
    const domainsTable = document.getElementById('domainsTable').getElementsByTagName('tbody')[0];

    // Clear existing rows
    while (domainsTable.firstChild) {
    domainsTable.removeChild(domainsTable.firstChild);
    }

    // Add rows for existing domains
    existingDomains.forEach(domain => {
    const newRow = domainsTable.insertRow();
    newRow.innerHTML = `
        <td class="border px-4 py-2">
            <input type="text" name="domains[]" value="${domain}" class="w-full">
        </td>
        <td class="border px-4 py-2">
            <button type="button" class="removeDomain">Remove</button>
        </td>
        `;
    });

    // Update comparison
    compareDomains();
}

let initialDomains = [];
// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    initialDomains = Array.from(document.querySelectorAll('input[name="domains[]"]')).map(input => input.value);
    compareDomains();
    document.getElementById('matchDomains').addEventListener('click', matchDomains);
    document.getElementById('resetChanges').addEventListener('click', resetChanges);
});

function resetChanges() {
    const domainsTable = document.getElementById('domainsTable').getElementsByTagName('tbody')[0];

    // Clear existing rows
    while (domainsTable.firstChild) {
        domainsTable.removeChild(domainsTable.firstChild);
    }

    // Add rows for initial domains
    initialDomains.forEach(domain => {
        const newRow = domainsTable.insertRow();
        newRow.innerHTML = `
            <td class="border px-4 py-2">
                <input type="text" name="domains[]" value="${domain}" class="w-full">
            </td>
            <td class="border px-4 py-2">
                <button type="button" class="removeDomain">Remove</button>
            </td>
        `;
    });

    // Update comparison
    compareDomains();
}



document.getElementById('resetChanges').addEventListener('click', resetChanges);
document.addEventListener('input', (e) => {
    if (e.target && e.target.name === 'domains[]') {
    compareDomains();
    }
});

// Enforce full state names
document.addEventListener('DOMContentLoaded', function() {
    const stateInput = document.querySelector('input[name="State"]');
    const errorMessage = stateInput.nextElementSibling;

    stateInput.addEventListener('input', function() {
        if (this.validity.valid) {
            errorMessage.style.display = 'none';
        } else {
            errorMessage.style.display = 'block';
        }
    });

    document.querySelector('form').addEventListener('submit', function(event) {
        if (!stateInput.validity.valid) {
            event.preventDefault();
            errorMessage.style.display = 'block';
        }
    });
});
</script>
</body>
</html>
