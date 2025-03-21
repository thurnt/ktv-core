jQuery(document).ready(function($) {
    // Handle tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).attr('href');

        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Show target content
        $('.tab-content').hide();
        $(targetId).show();
    });

    function generateChecksum(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16).padStart(8, '0');
    }

    function generateComplexApiKey() {
        // Generate timestamp component (8 chars)
        const timestamp = Date.now().toString(16).slice(-8).padStart(8, '0');

        // Generate random component (16 chars)
        const randomBytes = new Uint8Array(8);
        window.crypto.getRandomValues(randomBytes);
        const randomHex = Array.from(randomBytes)
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');

        // Combine timestamp and random components
        const baseKey = timestamp + randomHex;

        // Generate checksum (8 chars)
        const checksum = generateChecksum(baseKey);

        return baseKey + checksum;
    }

    $('#generate-api-key').on('click', function(e) {
        e.preventDefault();
        const apiKey = generateComplexApiKey();
        $('input[name="bluetag_api_key"]').val(apiKey);
    });

    // Add copy button functionality
    const $apiKeyField = $('input[name="bluetag_api_key"]');
    const $copyButton = $('<button>', {
        type: 'button',
        class: 'button button-secondary',
        text: 'Copy',
        css: { marginLeft: '5px' }
    });

    $copyButton.insertAfter('#generate-api-key');

    $copyButton.on('click', function(e) {
        e.preventDefault();
        $apiKeyField[0].select();
        document.execCommand('copy');
        
        const $this = $(this);
        const originalText = $this.text();
        $this.text('Copied!');
        
        setTimeout(function() {
            $this.text(originalText);
        }, 1500);
    });
});