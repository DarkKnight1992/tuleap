/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { utils, writeFile } from "xlsx";
import type { ReportSection } from "../../Data/data-formator";
import type { CellObjectWithExtraInfo } from "@tuleap/plugin-docgen-xlsx";
import {
    buildSheetTextCell,
    createMerges,
    fitColumnWidthsToContent,
    fitRowHeightsToContent,
    transformReportCellIntoASheetCell,
} from "@tuleap/plugin-docgen-xlsx";
import type { ExportSettings } from "../../export-document";

export function downloadXLSX(export_settings: ExportSettings, formatted_data: ReportSection): void {
    const book = utils.book_new();
    const cells = buildContent(export_settings, formatted_data);
    const sheet = utils.aoa_to_sheet(cells);
    sheet["!cols"] = fitColumnWidthsToContent(cells);
    sheet["!rows"] = fitRowHeightsToContent(cells);
    sheet["!merges"] = createMerges(cells);
    utils.book_append_sheet(book, sheet);
    writeFile(
        book,
        export_settings.first_level.tracker_name +
            "-" +
            export_settings.first_level.report_name +
            ".xlsx",
        {
            bookSST: true,
        }
    );
}

function buildContent(
    export_settings: ExportSettings,
    formatted_data: ReportSection
): Array<Array<CellObjectWithExtraInfo>> {
    const content: CellObjectWithExtraInfo[][] = [];
    const report_columns_label: CellObjectWithExtraInfo[] = [];
    const report_trackers_names: CellObjectWithExtraInfo[] = [];
    if (formatted_data.headers && formatted_data.headers.length > 0) {
        report_trackers_names.push({
            ...buildSheetTextCell(export_settings.first_level.tracker_name),
            ...(formatted_data.headers.length > 0 ? { character_width: 10 } : {}),
            merge_columns: formatted_data.headers.length - 1,
        });
        content.push(report_trackers_names);

        for (const header of formatted_data.headers) {
            report_columns_label.push(transformReportCellIntoASheetCell(header));
        }
        content.push(report_columns_label);
    }

    let artifact_value_rows: CellObjectWithExtraInfo[] = [];
    if (formatted_data.rows && formatted_data.rows.length > 0) {
        for (const row of formatted_data.rows) {
            artifact_value_rows = [];
            for (const cell of row) {
                artifact_value_rows.push(transformReportCellIntoASheetCell(cell));
            }
            content.push(artifact_value_rows);
        }
    }

    return content;
}
