import { Application } from "./vendor/stimulus.js"
import SmartExportLoaderController from "./controllers/smart_export_loader_controller.js"
import TabsController from "./controllers/tabs_controller.js"
import ColChipsController from "./controllers/col_chips_controller.js"
import SmartExportCountController from "./controllers/smart_export_count_controller.js"
import SmartExportFilterController from "./controllers/smart_export_filter_controller.js"
import FormatController from "./controllers/format_controller.js"

// Separate Stimulus application from the admin pages' own (public/js/app.js,
// window.Stimulus) — this one only ever registers the popup's own controllers,
// under identifiers prefixed smart-export-* to avoid colliding with anything
// a host application already runs. window.SmartExportStimulus guards against
// double-bootstrapping if this script is ever loaded more than once on a page
// (shouldn't happen — smart_export_popup() only prints it on its first call
// per request — but ES module top-level code only runs once per URL anyway).
const application = window.SmartExportStimulus ?? Application.start()
application.register('smart-export-loader', SmartExportLoaderController)
application.register('smart-export-tabs', TabsController)
application.register('smart-export-columns', ColChipsController)
application.register('smart-export-count', SmartExportCountController)
application.register('smart-export-filter', SmartExportFilterController)
application.register('smart-export-format', FormatController)
window.SmartExportStimulus = application
