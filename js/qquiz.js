
var qQuizClock, qQuizTimeout;
var qQuizIdleTime = 1; // Time before answer check (in seconds)

/**
 * Adds a question in the questions metabox.
 * 
 * @returns {void}
 */
function addQQuestion() {
    
    // Remove 'no questions yet' paragraph.
    jQuery('#qquiz-no-questions').remove();
    
    // Markup for question field
    var html = '<div class="qquiz-meta-fieldset">';
    
        // Question
        html += '<div class="qquiz-meta-field-question">';
            html += '<p><strong>Question</strong><br>';
            html += '<input type="text" class="regular-text" name="qquiz-questions[]">';
        html += '</div>';
        
        // Answer
        html += '<div class="qquiz-meta-field-answer">';
            html += '<p><strong>Answer</strong><br>';
            html += '<input type="text" class="regular-text" name="qquiz-answers[]">';
        html += '</div>';
        
        // Delete
        html += '<div class="qquiz-meta-field-delete">';
            html += '<p class="hide-if-no-js" id="qquiz-add-question">';
            html += '<a class="button" href="#" onclick="event.preventDefault(); deleteQQuestion(this);">Remove</a>';
            html += '</p>';
        html += '</div>';
        
    html += '</div>';
    
    // Append
    jQuery('#qquiz-meta-questions-list').append(html);
    
    // Fade in
    jQuery('.qquiz-meta-fieldset').fadeIn("fast");
    
}

/**
 * Starts the quiz.
 * 
 * @returns {void}
 */
function startQQuiz() {
    
    jQuery(document).ready(function() {
    
        // Remove start button
        jQuery('.qquiz-start').fadeOut(100, function() { jQuery('.qquiz-start').remove(); });
        
        // Fade in quiz
        jQuery('.qquiz-container').fadeIn("slow");

        // New countdown clock
        qQuizClock = jQuery('#qquiz-timer').FlipClock({
            clockFace: 'MinuteCounter',
            countdown: true,
            autoStart: false,
            callbacks: {
                stop: function() {
                    setTimeout(function(){
                        stopQQuiz();
                    },2000); 
                }
            }
        });

        // Set clock time
        qQuizClock.setTime(quizTime);
      
        // 1 second delay before clock start
        setTimeout(function(){
            qQuizClock.start();
        },1000);
        
    });
    
}

/**
 * Ends the quiz.
 * 
 * @returns {void}
 */
function stopQQuiz() {
    
    // Fade out quiz and timer
    jQuery('#qquiz-timer').fadeOut(300, function() { jQuery('.qquiz-content').remove(); });
    jQuery('.qquiz-content').fadeOut(300, function() { jQuery('.qquiz-content').remove(); });
    
    // Fade in summary
    jQuery('#qquiz-summary').fadeIn('slow');
    
}

/**
 * Checks an inputted answer.
 * 
 * @param {object} The question input element.
 * @param {string} The correct answer.
 */
function checkQAnswer(element,ans) {
    
    // Remove shake effect
    jQuery(element).removeClass('qquiz-wrong');
    
    // Get question and answer
    var guess = jQuery(element).val();
    var ans = atob(ans);
    
    // Pause before check
    clearTimeout(qQuizTimeout);
    qQuizTimeout = setTimeout(function(){
        
        // Check levenshtein distance
        if (jQuery(element).not('[readonly]') && 
                getEditDistance(guess.toLowerCase(),ans.toLowerCase()) <= quizDist) {
            
            // Correct!
            qQuizCorrect ++;
            
            // Disable input
            jQuery(element).val(ans);
            jQuery(element).attr('readonly', 'readonly');
            jQuery(element).blur();
            
            // Correct effect
            jQuery(element).addClass('qquiz-correct');
            
            // Check if quiz complete
            if (qQuizCorrect == qQuizCount) {
                
                // Summary
                statQQuiz('Correct Answers',qQuizCorrect + " / " + qQuizCount);
                statQQuiz('Time Taken',secondsToHMS(quizTime - qQuizClock.getTime()));
                
                // Stop quiz
                qQuizClock.stop();
                
            } else {
            
                // Highlight next input
                jQuery(element).parent().parent().next('.qquiz-container').find('.qquiz-answer input').focus();
                
            }

        } else {
            
            // Incorrect, shake box
            jQuery(element).addClass('qquiz-wrong');
            jQuery(element).val('');
            
        }
        
    }, qQuizIdleTime * 800);
    
}

/**
 * Append summary statistics.
 * 
 * @param {string} label The label for the stat.
 * @param {string} value The value for the stat.
 * @returns {void}
 */
function statQQuiz(label,value) {
    
    var html = '<span class="qquiz-label">'+label+': </span>';
    html += '<span class="qquiz-value">'+value+'</span><br>';
    jQuery('#qquiz-summary .qquiz-stats').append(html);
    
}

/**
 * Deletes a question from the meta box.
 * 
 * @param {object} The delete button element.
 */
function deleteQQuestion(element) {
    var par = jQuery(element).parent().parent().parent();
    jQuery(par).fadeOut(300, function() { jQuery(par).remove(); });
}

/**
 * Returns the edit distance between 2 strings.
 * 
 * @author Andrei Mackenzie <https://github.com/andrei-m>
 * @author Milot Mirdita <https://gist.github.com/milot-mirdita>
 * @author Clément <https://gist.github.com/kigiri>
 * @copyright 2011 Andrei Mackenzie
 * @license MIT License
 * 
 * @param {string} a The first string.
 * @param {string} b The second string.
 * 
 * @returns {number}
 */
function getEditDistance(a, b) {
    
    if (a.length === 0) return b.length;
    if (b.length === 0) return a.length;

    var matrix = [];

    // increment along the first column of each row
    var i;
    for (i = 0; i <= b.length; i++) {
        matrix[i] = [i];
    }

    // increment each column in the first row
    var j;
    for (j = 0; j <= a.length; j++) {
        matrix[0][j] = j;
    }

    // Fill in the rest of the matrix
    for (i = 1; i <= b.length; i++) {
        for (j = 1; j <= a.length; j++) {
            if (b.charAt(i - 1) === a.charAt(j - 1)) {
                matrix[i][j] = matrix[i - 1][j - 1];
            } else {
                matrix[i][j] = Math.min(matrix[i - 1][j - 1] + 1,   // Substitution
                                    Math.min(matrix[i][j - 1] + 1,  // Insertion
                                        matrix[i - 1][j] + 1));     // Deletion
            }
        }
    }

    return matrix[b.length][a.length];
    
}

/**
 * Convert seconds to H:M:S format.
 * 
 * @param {number} s The number of seconds.
 * @returns {String}
 */
function secondsToHMS(s) {
    var h = Math.floor(s/3600); //Get whole hours
    s -= h*3600;
    var m = Math.floor(s/60); //Get remaining minutes
    s -= m*60;
    return h+":"+(m < 10 ? '0'+m : m)+":"+(s < 10 ? '0'+s : s); //zero padding on minutes and seconds
}