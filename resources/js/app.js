import './bootstrap';
import './tenant-money-format';
import * as visitorContactNormalize from './shared/visitorContactNormalize';

if (typeof window !== 'undefined') {
    window.RentBaseVisitorContactNormalize = visitorContactNormalize;
}
