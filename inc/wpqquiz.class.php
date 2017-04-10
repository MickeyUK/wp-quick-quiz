<?php

/**
 * Add interactive quizzes to your blog.
 *
 * @category    Plugin
 * @package     WordPress Quick Quiz
 * @author      Michael Dearman <mickeyuk@live.co.uk>
 * @license     http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link        https://github.com/MickeyUK/wp-quick-quiz
 */
class WPQQuiz {
    
    /**
     * This loads settings for the plugin and adds the hooks.
     */
    public static function init() {
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array('WPQQuiz','enqueue_scripts'));
        add_action('add_meta_boxes', array('WPQQuiz','meta_box_add'), 10, 2 );
        add_action('save_post', array('WPQQuiz', 'meta_box_save'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array('WPQQuiz','enqueue_scripts'));
        add_filter('the_content', array('WPQQuiz', 'the_content'));
        
    }
    
    /**
     * Enqueues scripts and styles needed by the plugin.
     */
    public static function enqueue_scripts() {
        
        // CSS
        wp_enqueue_style('flipclock-css', plugin_dir_url(WPQQUIZ_FILE).'css/flipclock.css', 
                array(), null, 'all');
        
        wp_enqueue_style('qquiz-css', plugin_dir_url(WPQQUIZ_FILE).'css/qquiz.css', 
                array(), null, 'all');
        
        // Javascript
        wp_enqueue_script('flipclock-js',plugin_dir_url(WPQQUIZ_FILE).'js/flipclock.js', 
                array( 'jquery' ), NULL, true );
        
        wp_enqueue_script('qquiz-js',plugin_dir_url(WPQQUIZ_FILE).'js/qquiz.js', 
                array( 'jquery' ), NULL, true );
        
    }
    
    /**
     * Adds the quiz meta box to the admin dashboard.
     */
    public static function meta_box_add() {
        
        // Settings
        add_meta_box('qquiz-meta-settings', 'Quick Quiz Settings', 
                array('WPQQuiz','meta_settings_markup'), null, 'side');
        
        // Questions
        add_meta_box('qquiz-meta-questions', 'Quick Quiz Questions',
                array('WPQQuiz','meta_questions_markup'));
        
    }
    
    /**
     * Sanitizes meta box form data and saves it.
     */
    public static function meta_box_save() {
     
        // Abort
        $nonce = $_POST['qquiz-meta-nonce'];
        if (!isset($nonce) ||
            !wp_verify_nonce( $nonce, 'qquiz-meta' )) {
            print 'Sorry, your nonce did not verify.';
            exit;
        }

        // Questions
        $questions = array();
        foreach($_POST['qquiz-questions'] as $q) {
            $questions[] = filter_var($q, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        }
        
        // Answers
        $answers = array();
        foreach($_POST['qquiz-answers'] as $a) {
            $answers[] = filter_var($a, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        }
        
        // Combine questions & answers
        $combined = array_combine($questions,$answers);
        
        // Settings
        $post_id = $_POST['post_ID'];
        $time = filter_input(INPUT_POST, 'qquiz-time', FILTER_SANITIZE_NUMBER_INT);
        $dist = filter_input(INPUT_POST, 'qquiz-dist', FILTER_SANITIZE_NUMBER_INT);
        
        // Add meta data
        update_post_meta($post_id, 'qquiz-questions', $combined);
        
        update_post_meta($post_id, 'qquiz-time', $time);
        update_post_meta($post_id, 'qquiz-dist', $dist);
        
    }
    
    /**
     * Markup for the settings metabox.
     * 
     * @param object $post The post data.
     */
    public static function meta_settings_markup($post) {
        
        wp_nonce_field('qquiz-meta', 'qquiz-meta-nonce');
        
        // Time
        $time = get_post_meta($post->ID,'qquiz-time',true);
        
        echo '<div style="margin: 20px 0 10px 0;">';
        echo '<strong>Time</strong><br>';
        self::input_number('qquiz-time', (empty($time) ? 240 : $time), 
                'The time the user has to complete the quiz (in seconds).');
        echo '</div>';
        
        // Correct Answer Distance
        $dist = get_post_meta($post->ID,'qquiz-dist',true);
        
        echo '<div style="margin: 20px 0;">';
        echo '<strong>Correct Answer Distance</strong><br>';
        self::input_number('qquiz-dist', (empty($dist) ? 2 : $dist), 
                'This sets the Levenshtein distance of the answer checking. '
                . 'Lower the value to make it more strict, higher for more lenient.');
        echo '</div>';
        
    }
    
    /**
     * Markup for the questions metabox.
     * 
     * @param object $post The post data.
     */
    public static function meta_questions_markup($post) {
        
        wp_nonce_field('qquiz-meta', 'qquiz-meta-nonce');
        
        // Questions
        $questions = get_post_meta($post->ID, "qquiz-questions", true);
        
        echo '<div id="qquiz-meta-questions-list">';
        
        if (empty($questions)) {
            
            // No existing questions
            echo '<p id="qquiz-no-questions">No questions yet.</p>';
            
        } else { 
            
            foreach($questions as $question => $answer) {
            ?>
            
            <div class="qquiz-meta-fieldset" style="display: block;">
                <div class="qquiz-meta-field-question">
                    <p><strong>Question</strong><br>
                    <input value="<?php echo stripslashes($question); ?>" type="text" 
                           class="regular-text" name="qquiz-questions[]">
                </div>
        
                <div class="qquiz-meta-field-answer">
                    <p><strong>Answer</strong><br>
                    <input value="<?php echo stripslashes($answer); ?>" type="text" 
                           class="regular-text" name="qquiz-answers[]">
                </div>
                
                <div class="qquiz-meta-field-delete">
                    <p class="hide-if-no-js" id="qquiz-add-question">
                        <a class="button" href="#" onclick="event.preventDefault(); deleteQQuestion(this);">Remove</a>
                    </p>
                </div>
            </div>
         
            <?php
            }
            
        }
        
        echo '</div>';
        
        // Add question button
        echo '<p class="hide-if-no-js" id="qquiz-add-question">';
        echo '<a class="button" href="#" onclick="event.preventDefault(); addQQuestion()">Add Question</a>';
        echo '</p>';
        
    }
    
    /**
     * Where the magic happens. Appends quiz content to posts.
     * 
     * @global object $wp_query The post query.
     * @param string $content The post content.
     * 
     * @return string
     */
    public static function the_content($content) {
        
        global $post;
        
        // Check for quiz content
        $questions = get_post_meta($post->ID,'qquiz-questions', true);
        if (!empty($questions)) {
            
            // Settings
            $time = get_post_meta($post->ID,'qquiz-time', true);
            $dist = get_post_meta($post->ID,'qquiz-dist', true);
            
            $content .= '<script>';
                $content .= 'var quizTime = '.((empty($time)) ? '240' : $time).';';
                $content .= 'var quizDist = '.((empty($dist)) ? '2' : $dist).';';
                $content .= 'var qQuizCount = '.count($questions).';';
                $content .= 'var qQuizCorrect = 0;';
            $content .= '</script>';
            
            // Summary box
            $content .= '<div id="qquiz-summary">';
            $content .= '<h2 class="">Quiz Over</h2>';
            $content .= '<div class="qquiz-stats"></div>';
            $content .= '</div>';
            
            // Control box
            $content .= '<div class="qquiz-control">';
                $content .= '<div id="qquiz-timer"></div>';
                $content .= '<button class="qquiz-start" onclick="startQQuiz()">Start Quiz</button>';
            $content .= '</div>';
            
            // Quiz container
            $content .= '<div class="qquiz-content">';
            
            // Questions
            foreach($questions as $question => $answer) {
                
                $content .= '<div class="qquiz-container">';
                
                // Question
                $content .= '<div class="qquiz-question"><label>';
                $content .= stripslashes($question);
                $content .= '</label></div>';
                
                // Answer
                $content .= '<div class="qquiz-answer">';
                    $content .= '<input type="text" onkeyup="checkQAnswer(this,\'';
                    $content .= base64_encode(stripslashes($answer));
                    $content .= '\')"/>';
                $content .= '</div>';
                
                $content .= '</div>';
                
            }
            
            // End table
            $content .= '</div>';
            
        }
        
        // Return altered content
        return $content;
        
    }
    
    /**
     * Displays an input number field.
     * 
     * @param string $name The name and ID for the input field.
     * @param string $value The value for the input field.
     * @param string $description A description for the field.
     */
    public static function input_number($name, $value = "", $description = "") {

        // ARIA
        $desc = ($description != '') ? 'aria-describedby = "' . $name . '-description"' : '';

        // Input field
        echo sprintf('<input type="number" id="%1$s" name="%1$s" value="%2$s" %3$s/>', $name, $value, $desc);

        // Description
        if ($description != "") {
            echo '<p id="' . $name . '-description" class="description">' . $description;
        }
        
    }
    
}