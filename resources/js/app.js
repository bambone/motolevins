import './bootstrap';
import './tenant-money-format';
import './tenant-contact-inquiry-form.js';
import './tenant-public-review-submit.js';
import * as visitorContactNormalize from './shared/visitorContactNormalize';
import * as publicFormSuccessUi from './shared/publicFormSuccessUi';
import './shared/tenantKeyboardA11y.js';

if (typeof window !== 'undefined') {
    window.RentBaseVisitorContactNormalize = visitorContactNormalize;
    window.RentBasePublicFormSuccessUi = publicFormSuccessUi;
}
