<div class="row">
    <div class="col-md-8">
        {{ Form::checkbox('uddoktapay_enabled', trans('setting::attributes.uddoktapay_enabled'), trans('setting::settings.form.enable_uddoktapay'), $errors, $settings) }}
        {{ Form::text('translatable[uddoktapay_label]', trans('setting::attributes.translatable.uddoktapay_label'), $errors, $settings, ['required' => true]) }}
        {{ Form::textarea('translatable[uddoktapay_description]', trans('setting::attributes.translatable.uddoktapay_description'), $errors, $settings, ['rows' => 3, 'required' => true]) }}
        
        {{-- Test Mode Checkbox --}}
        {{ Form::checkbox('uddoktapay_test_mode', trans('setting::attributes.uddoktapay_test_mode'), trans('setting::settings.form.use_sandbox_for_test_payments'), $errors, $settings) }}

        <div class="{{ old('uddoktapay_enabled', array_get($settings, 'uddoktapay_enabled')) ? '' : 'hide' }}" id="uddoktapay-fields">
            
            {{-- Sandbox Environment --}}
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">For Sandbox Environment Only</h5>
                </div>
                <div class="card-body">
                    {{ Form::text('uddoktapay_sandbox_api_url', 'Sandbox API URL', $errors, $settings, ['required' => true, 'placeholder' => 'Get sandbox api url from uddoktapay developer documentation', 'value' => old('uddoktapay_sandbox_api_url', array_get($settings, 'uddoktapay_sandbox_api_url', 'https://sandbox.uddoktapay.com/api/checkout-v2'))]) }}
                    
                    {{ Form::text('uddoktapay_sandbox_api_key', 'Sandbox API Key', $errors, $settings, ['required' => true, 'placeholder' => 'Get sandbox api key from uddoktapay developer documentation0', 'value' => old('uddoktapay_sandbox_api_key', array_get($settings, 'uddoktapay_sandbox_api_key', '982d381360a69d419689740d9f2e26ce36fb7a50'))]) }}
                </div>
            </div>
            
            {{-- Live Environment --}}
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">For Live Environment Only</h5>
                </div>
                <div class="card-body">
                    {{ Form::text('uddoktapay_live_api_url', 'Live API URL', $errors, $settings, ['required' => true, 'placeholder' => 'Get your api url from self hosted panel', 'value' => old('uddoktapay_live_api_url', array_get($settings, 'uddoktapay_live_api_url'))]) }}
                    
                    {{ Form::text('uddoktapay_live_api_key', 'Live API Key', $errors, $settings, ['required' => true, 'placeholder' => 'Get your api key from self hosted panel', 'value' => old('uddoktapay_live_api_key', array_get($settings, 'uddoktapay_live_api_key'))]) }}
                    
                    {{ Form::text('uddoktapay_live_verify_url', 'Live Verify URL', $errors, $settings, ['required' => true, 'placeholder' => 'Get your verify url from self hosted panel', 'value' => old('uddoktapay_live_verify_url', array_get($settings, 'uddoktapay_live_verify_url'))]) }}
                    
                    {{ Form::text('uddoktapay_live_refund_url', 'Live Refund URL', $errors, $settings, ['placeholder' => 'Get your refund url from self hosted panel', 'value' => old('uddoktapay_live_refund_url', array_get($settings, 'uddoktapay_live_refund_url'))]) }}
                </div>
            </div>
            
            {{-- Display Current Configuration --}}
            <div class="card mb-3">
                <label>Current Configuration</label>
                <div class="form-control-plaintext">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Mode:</strong> <span id="current-mode">Sandbox</span>
                        </div>
                        <div class="col-md-3">
                            <strong>API URL:</strong> <span id="current-api-url">https://sandbox.uddoktapay.com/api/checkout-v2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
 $(document).ready(function() {
    // Function to update the displayed current configuration
    function updateCurrentConfig() {
        var isTestMode = $("#uddoktapay_test_mode").is(':checked');
        var currentMode = $('#current-mode');
        var currentApiUrl = $('#current-api-url');
        
        if (isTestMode) {
            currentMode.text('Sandbox');
            currentApiUrl.text($('#uddoktapay_sandbox_api_url').val());
        } else {
            currentMode.text('Live');
            currentApiUrl.text($('#uddoktapay_live_api_url').val());
        }
    }

    // Toggle UddoktaPay fields visibility
    $("#uddoktapay_enabled").on("change", function() {
        $("#uddoktapay-fields").toggleClass("hide");
    });

    // Update current configuration when test mode changes
    $("#uddoktapay_test_mode").on("change", function() {
        updateCurrentConfig();
    });
    
    // Update current configuration when API URLs change
    $("#uddoktapay_sandbox_api_url, #uddoktapay_live_api_url").on("input", function() {
        updateCurrentConfig();
    });

    // Set the correct configuration on page load
    updateCurrentConfig();
});
</script>
