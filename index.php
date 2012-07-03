<?php
session_start();
include "updates.php";
$APP_NAME = "Minipets Database";
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $APP_NAME ?></title>
    <link href='http://fonts.googleapis.com/css?family=Nobile:400,400italic,700' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="minipetter.css">
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, user-scalable=no, maximum-scale=1.0">
<?php // This meta allows the user to place the page on their home screen and
      // launch it like a native app, in a separate, UI-less version of Safari
?>
    <meta name="apple-mobile-web-app-capable" content="yes" />
<?php // Workaround for IE<9 lack of support for media queries ?>
 <!--[if lt IE 9]>
     <style>
      /* These rules are workarounds for IE<9 lack of media query support. */
      body.medium_screen #page_title {
          background-image: none;
          width: 369px;
          height: 158px;
          filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(
              src='logo.png', sizingMethod='scale');
      }

      body.medium_screen #main_wrapper {
        top: 18em;
      }

      body.medium_screen #authors {
        top: 0.7em;
        left: 0.5em;
        bottom: auto;
        font-size: 0.7em;
        color: #888;
      }

      body.medium_screen #login_panel {
        top: 0.5em;
      }
    </style>
 <![endif]-->

  </head>

<?php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == 'true') {
    echo '  <body class="logged_in">';
} else {
    echo '  <body>';
}
?>
    <div id="login_panel">
      <label for="password">Admin password:
        <input id="password"></label>

      <a class="button" href="#log_in">log in</a>
      <a class="button log_out" href="#log_out">log out</a>
    </div>

    <div id="authors">Minipets Database project by <a href="http://caerphoto.com/">Andy</a> and
    <a href="http://www.alternative-blog.net/">The Godmother</a>. Source <a href="https://github.com/caerphoto/minipetter">available</a> on GitHub.</div>

    <h1 id="page_title">Minipetter</h1>

    <p>What's this all about? :: Click <a href="http://www.alternative-blog.net/2012/05/presenting-minipets-database-q.html">here</a> for Details!</p>

    <div id="list_controls" class="visible">
      <label for="list_filter" class="search"><input id="list_filter" type="search"
        placeholder="enter pet name"></label>

      <label for="filter_by_type">Type:
        <select id="filter_by_type"></select></label>
      <label for="filter_by_type">Rarity:
        <select id="filter_by_rarity"></select></label>
      <label for="filter_by_type">Source:
        <select id="filter_by_source"></select></label>

      <button id="reset_filters">reset</button>

      <a class="button visible" href="#add" title="Shortcut: A">add pet</a>
      <a class="button visible" href="#new_update">new update</a>
    </div>

    <div id="main_wrapper">
      <noscript>
        <p id="o_noes_js">O noes! It seems you don't have JavaScript enabled in your browser. This site can't function without it, unfortunately, so you'll need to re-enable it before you can see all the minipet info.</p>
      </noscript>
      <p id="list_hint" style="display: none">Select a pet from the list on the right &rarr;</p>
      <script> document.getElementById("list_hint").style.display = ""; </script>

      <h2 id="last_update_date"><?php echo $last_update_date; ?></h2>
      <p id="last_update_value"><?php echo $last_update_value; ?></p>

      <div id="pet_count"><span></span> pets</div>

      <ul id="pet_list" class="visible">
        <li class="loading"><div class="loading_spinner">&#9650;</div>Loading pet data&hellip;</li>
      </ul>

      <div id="popup_box"></div>
      <div id="editor"></div>

      <div id="updater">
        <label for="update_text">Add new update:</label>
        <div class="textarea_wrapper">
          <textarea name="update_text"></textarea>
        </div>
        <a class="button change" href="#save_update">save</a>
      </div>

    </div>

<?php
// List filter templates
// ---------------------
?>

    <script type="text/mustache" id="filter_by_type_tmpl">
      <option value="-1">Any</option>
    {{#options}}
      <option value="{{idx}}">{{txt}}</option>
    {{/options}}
    </script>

    <script type="text/mustache" id="filter_by_rarity_tmpl">
      <option value="-1">Any</option>
    {{#options}}
      <option value="{{idx}}">{{txt}}</option>
    {{/options}}
    </script>

    <script type="text/mustache" id="filter_by_source_tmpl">
      <option value="-1">Any</option>
    {{#options}}
      <option value="{{idx}}">{{txt}}</option>
    {{/options}}
    </script>

<?php
// List template
// ---------------
?>
    <script type="text/mustache" id="list_tmpl">
      {{#pet_data}}
        <li class="alpha_marker"><h2 class="alpha collapsed">{{alpha}}</h2>
        <ul>
        {{#pets}}
          <li class="pet" id="pet_{{short_name}}">
            <a href="#{{short_name}}" class="pet_link {{rarity_txt}}">{{long_name}}</a>
            <a class="button delete" href="#{{short_name}}/delete" title="Delete pet">&times;</a>
          </li>
        {{/pets}}
        </ul></li>
      {{/pet_data}}
    </script>

<?php
// Popup template
// ---------------
?>
    <script type="text/mustache" id="popup_tmpl">
      <div class="box_buttons">
        <a class="button change" href="#{{short_name}}/edit" title="Shortcut: E">edit</a>
      </div>

      <h2 id="popup_name">{{long_name}}</h2>
      <div class="box_left">
        <img id="popup_img" class="box_img"
        {{#img_src}}
          src="{{img_src}}"
        {{/img_src}}
        {{^img_src}}
          src="img.php?name={{short_name}}"
        {{/img_src}}
          width="200" height="200" alt="Picture of {{long_name}}">


      {{#loc}}
        <div class="box_coords">{{x}}, {{y}}</div>
      {{/loc}}

        <div class="box_loc">{{loc}}</div>
      </div>

      <div class="box_info">
        <p class="details">
          <label>Type:</label> <span>{{type}}</span><br>
          <label>Rarity:</label> <span>{{rarity}}</span><br>
          <label>Source:</label> <span>{{source}}</span><br>
        </p>
        <p class="info">{{{info}}}</p>
      {{#flavour_text}}
        <p class="flavour_text">{{flavour_text}}</p>
      {{/flavour_text}}
      </div>
      <div id="button_wrapper">
        <a class="button" id="close_popup" href="#/">back to pet list</a>
      </div>
    </script>

<?php
// Editor template
// ---------------
?>
    <script type="text/mustache" id="editor_tmpl">
      <div class="box_buttons">
        <a class="button change" href="#{{#long_name}}
{{short_name}}/save
{{/long_name}}
{{^long_name}}
create
{{/long_name}}"
        title="Shortcut: Ctrl + Enter">save</a>

        <a class="button" href="#{{#short_name}}
{{short_name}}
{{/short_name}}
{{^short_name}}
{{cancel_to}}
{{/short_name}}"
        title="Shortcut: Esc">cancel</a>
      </div>

      <form id="editor_form">
        <div class="field">
          <label for="long_name" accesskey="n">Name:</label>
          <input id="long_name_input" name="long_name" value="{{long_name}}"
          pattern="([A-Z][a-zA-Z\-.']* ?)+(\w|\d)">
          <span id="derived_short_name">(short name: <span id="sn">{{short_name}}</span>)</span>
        </div>

        <div class="field">
          <label for="img_src">Image URL:</label>
          <input name="img_src" type="url" value="{{img_src}}">
        </div>

        <div class="field">
          <label for="x">Coordinates:</label>
          <input name="x" value="{{x}}">, <input name="y" value="{{y}}">
        </div>

        <div class="field">
          <label for="loc">Location:</label>
          <input name="loc" value="{{loc}}">
        </div>

        <div class="field column">
          <label for="type">Type:</label>
          <select name="type">
          {{#types}}
            <option value="{{val}}">{{txt}}</option>
          {{/types}}
          </select>
        </div>

        <div class="field column">
          <label for="rarity">Rarity:</label>
          <select name="rarity">
          {{#rarities}}
            <option value="{{val}}">{{txt}}</option>
          {{/rarities}}
          </select>
        </div>

        <div class="field column">
          <label for="ob_via">Source:</label>
          <select name="ob_via">
          {{#sources}}
            <option value="{{val}}">{{txt}}</option>
          {{/sources}}
          </select>
        </div>

        <label for="info">Info:</label>
        <div class="textarea_wrapper">
          <textarea name="info">{{info}}</textarea>
        </div>
      </form>
    </script>

    <script>
        // arrays generated by server
        var config = {
            fields: <?php echo json_encode($db_cols); ?>,
            types: <?php echo json_encode($types); ?>,
            sources: <?php echo json_encode($sources); ?>,
            rarities: <?php echo json_encode($rarities); ?>,
            app_name: "<?php echo $APP_NAME; ?>"
        };
    </script>
    <script src="//caerphoto.com/jquery.js"></script>
    <script src="//caerphoto.com/mustache.js"></script>
    <script src="minipetter.js"></script>
  </body>
</html>

