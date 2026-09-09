import { Application } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"
import FormCollectionController from "./controllers/form_collection_controller.js"
import DialogController from "./controllers/dialog_controller.js"
import DualListController from "./controllers/dual_list_controller.js"
import CountController from "./controllers/count_controller.js"
import FilterController from "./controllers/filter_controller.js"

const application = Application.start()
application.register("form-collection", FormCollectionController)
application.register("dialog", DialogController)
application.register("dual-list", DualListController)
application.register("count", CountController)
application.register("filter", FilterController)

window.Stimulus = application
