# Context
In larger installations of Open Journal Systems, it is necessary to devolve control to Section Editors. In order to preserve operational integrity and peer review, however, it is sometimes desirable for editors to approve the final decision of the Section Editor before it is sent to the Author. Such a workflow allows Editors to ensure that the peer review procedure for the journal has been correctly undertaken and that correspondence from Section Editors to Authors is appropriate and professional. At present, Open Journal Systems does not support this workflow.

# OJS-Draft-Editorial
This patch for OJS 2.4.3 inserts logic to force Section Editors to have their decisions approved by Editors before they are emailed out to authors. This replaces the standard "accept"/"revise"/"decline" workflow with a draft decision that can be saved before it is approved by an Editor.

# Installation
OJS-Draft-Editorial can be installed by using the GNU patch tool from the root of your OJS installation:

> $ patch -p1 < editorial_decision_draft.patch
> $ mysql -u xxxx -p ojs_database < tbl_draft_decisons.sql

To apply this patch to an OJS installation running on PSQL or MSSQL you will need to recreate the tbl_draft_decisions.sql in your own syntax.

# Credit
OJS-Draft-Editorial was written by Andy Byers at [Ubiquity Press](http://ubiquitypress.com/) for the [Open Library of Humanities](https://www.openlibhums.org).