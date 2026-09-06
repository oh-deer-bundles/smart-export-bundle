import { Application } from "https://unpkg.com/@hotwired/stimulus/dist/stimulus.js"
import FormCollectionController from "./controllers/form_collection_controller.js"
import DialogController from "./controllers/dialog_controller.js"
import DualListController from "./controllers/dual_list_controller.js"

const application = Application.start()
application.register("form-collection", FormCollectionController)
application.register("dialog", DialogController)
application.register("dual-list", DualListController)

window.Stimulus = application
