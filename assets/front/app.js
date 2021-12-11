
// any CSS you import will output into a single css file (app.css in this case)
import '../styles/front/app.scss';

// start the Stimulus application
import '../bootstrap';

// jquery, popper & bootstrap
const $ = require('jquery');
window.Popper = require('popper.js');
global.$ = global.jQuery = $;
require('bootstrap');

require('./navbar');