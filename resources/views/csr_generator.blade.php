<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CSR Generator</title>
    <link href="{{ URL::asset('styles.css') }}" rel="stylesheet" type="text/css">
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            let domainIndex = 1;

            const toggleRequiredAttributes = (isPdfUpload) => {
                const manualInputs = document.querySelectorAll('#manual-input input');
                manualInputs.forEach(input => {
                    if (isPdfUpload) {
                        input.removeAttribute('required');
                    } else {
                        input.setAttribute('required', 'required');
                    }
                });
            };

            document.getElementById('add-domain').addEventListener('click', function(e) {
                e.preventDefault();
                domainIndex++;
                let newDomainField = document.createElement('div');
                newDomainField.className = 'domain-field';
                newDomainField.innerHTML = `
            <input type="text" name="domain_name[]" placeholder="Domain Name" required>
            <button class="remove-domain">-</button>
        `;
                document.getElementById('domain-fields').appendChild(newDomainField);

                newDomainField.querySelector('.remove-domain').addEventListener('click', function(e) {
                    e.preventDefault();
                    if (document.querySelectorAll('.domain-field').length > 1) {
                        newDomainField.remove();
                    } else {
                        alert("You must have at least one domain.");
                    }
                });
            });

            document.querySelectorAll('.remove-domain').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (document.querySelectorAll('.domain-field').length > 1) {
                        this.parentNode.remove();
                    } else {
                        alert("You must have at least one domain.");
                    }
                });
            });

            document.getElementById('toggle-input').addEventListener('change', function(e) {
                const isPdfUpload = this.checked;
                const manualInputSection = document.getElementById('manual-input');
                const pdfUploadSection = document.getElementById('pdf-upload');

                if (isPdfUpload) {
                    manualInputSection.style.display = 'none';
                    pdfUploadSection.style.display = 'block';
                } else {
                    manualInputSection.style.display = 'block';
                    pdfUploadSection.style.display = 'none';
                }

                toggleRequiredAttributes(isPdfUpload);
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                const isPdfUpload = document.getElementById('toggle-input').checked;
                if (isPdfUpload) {
                    this.action = "{{ route('generate.csr.pdf') }}";
                } else {
                    this.action = "{{ route('generate.csr') }}";
                }
            });

            // Initialize required attributes based on initial state
            toggleRequiredAttributes(document.getElementById('toggle-input').checked);
        });
    </script>
    <style>
        input[type=file]::file-selector-button {
            margin-top: 5px
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
<div class="csrgen">
    <h1>Enter CSR Contents</h1>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <label for="toggle-input">
        <input type="checkbox" id="toggle-input">
        Upload PDF for CSR
    </label>
    <form method="POST" enctype="multipart/form-data">
        @csrf
        <div id="manual-input">
            <label for="country">Country (2 Letter Country Code):</label>
            <input type="text" id="country" name="country" pattern="[A-Z]{2}" title="Country code must be exactly 2 upper-case letters" required>

            <label for="state">State (Full Name):</label>
            <input type="text" id="state" name="state" minlength="3" pattern="^[A-Za-z\s]{3,}$" title="Please enter the full state name (minimum 3 characters, no abbreviations)" required>
            <span id="stateError" class="error-message" style="color: red; display: none;">Please enter the full state name (minimum 3 characters, no abbreviations)</span>

            <label for="city">City:</label>
            <input type="text" id="city" name="city" required>

            <label for="organization">Organization:</label>
            <input type="text" id="organization" name="organization" required>

            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="domains"> Domains</label>
            <div id="domain-fields">
                <div class="domain-field">
                    <input type="text" name="domain_name[]" placeholder="Domain Name"
                           pattern="^(\*\.)?([^\.]+\.)*[^\.]+\.[^\.]+$"
                           title="Please enter a valid domain name" required>
                    <button class="remove-domain">-</button>
                </div>
            </div>
            <button id="add-domain">+</button>
        </div>

        <div id="pdf-upload" style="display:none;">
            <label for="pdf_file">Upload PDF:</label>
            <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
        </div>
        <input type="submit" value="Generate CSR">
    </form>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const stateInput = document.getElementById('state');
        const errorMessage = document.getElementById('stateError');

        stateInput.addEventListener('input', function() {
            if (this.validity.valid) {
                errorMessage.style.display = 'none';
            } else {
                errorMessage.style.display = 'block';
            }
        });

        stateInput.form.addEventListener('submit', function(event) {
            if (!stateInput.validity.valid) {
                event.preventDefault();
                errorMessage.style.display = 'block';
            }
        });
    });
</script>
</html>
