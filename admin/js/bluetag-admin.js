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

    $('#generate-api-key, #generate-test-api-key').on('click', function(e) {
        e.preventDefault();
        const apiKey = generateComplexApiKey();
        const inputName = $(this).attr('id') === 'generate-api-key' ? 'bluetag_api_key' : 'bluetag_test_api_key';
        $(`input[name="${inputName}"]`).val(apiKey);
    });

    // Handle token removal
    $('.bluetag-token-list').on('click', '.remove-token', function(e) {
        e.preventDefault();
        const button = $(this);
        const token = button.data('token');
        
        if (confirm('Are you sure you want to remove this token?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'remove_bluetag_token',
                    token: token,
                    nonce: bluetagSettings.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            if ($('.bluetag-token-list tbody tr').length === 0) {
                                $('.bluetag-token-list tbody').append('<tr><td colspan="7">No tokens found.</td></tr>');
                            }
                        });
                    } else {
                        alert('Failed to remove token: ' + response.data);
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred while removing the token.');
                    button.prop('disabled', false);
                }
            });
        }
    });

    // Add copy button functionality for both API key fields
    $('input[name="bluetag_api_key"], input[name="bluetag_test_api_key"]').each(function() {
        const $field = $(this);
        const $copyButton = $('<button>', {
            type: 'button',
            class: 'button button-secondary',
            text: 'Copy',
            css: { marginLeft: '5px' }
        });

        $copyButton.insertAfter($field.next('.button'));

        $copyButton.on('click', function(e) {
            e.preventDefault();
            $field[0].select();
            document.execCommand('copy');
            
            const $this = $(this);
            const originalText = $this.text();
            $this.text('Copied!');
            
            setTimeout(function() {
                $this.text(originalText);
            }, 1500);
        });
    });
});