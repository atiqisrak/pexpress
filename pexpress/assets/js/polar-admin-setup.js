/**
 * Polar Express Setup Wizard JavaScript
 *
 * @package PExpress
 * @since 1.0.5
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Setup wizard step navigation
        initWizardNavigation();
        
        // Copy shortcode functionality
        initCopyButtons();
        
        // Form validation
        initFormValidation();
    });

    /**
     * Initialize wizard navigation
     */
    function initWizardNavigation() {
        // Smooth scroll to top when changing steps
        $('.pexpress-wizard-navigation a').on('click', function() {
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        });

        // Add loading state to buttons
        $('.pexpress-btn-primary').on('click', function() {
            var $btn = $(this);
            if ($btn.closest('form').length) {
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> ' + $btn.text());
            }
        });
    }

    /**
     * Initialize copy buttons
     */
    function initCopyButtons() {
        $('.pexpress-copy-btn, .polar-copy-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var shortcode = $btn.data('shortcode');
            
            if (!shortcode) {
                // Try to get from code element
                var $code = $btn.siblings('code');
                if ($code.length) {
                    shortcode = $code.text().trim();
                } else {
                    var $shortcodeBox = $btn.closest('.pexpress-shortcode-box').find('code');
                    if ($shortcodeBox.length) {
                        shortcode = $shortcodeBox.text().trim();
                    }
                }
            }

            if (!shortcode) {
                return;
            }

            // Create temporary textarea
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                var originalHtml = $btn.html();
                $btn.html('<span class="dashicons dashicons-yes"></span>').addClass('copied');
                
                setTimeout(function() {
                    $btn.html(originalHtml).removeClass('copied');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            
            $temp.remove();
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Validate setup completion form
        $('form[action*="pexpress_complete_setup"]').on('submit', function(e) {
            // Add any validation logic here if needed
            return true;
        });

        // Validate skip setup form
        $('form[action*="pexpress_skip_setup"]').on('submit', function(e) {
            if (!confirm('Are you sure you want to skip the setup? You can always complete it later from the menu.')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }

    /**
     * Progress bar animation
     */
    function animateProgress() {
        $('.pexpress-progress-step.active').each(function() {
            var $step = $(this);
            $step.find('.pexpress-progress-circle').addClass('animate');
        });
    }

    // Animate progress on page load
    setTimeout(animateProgress, 300);

})(jQuery);

