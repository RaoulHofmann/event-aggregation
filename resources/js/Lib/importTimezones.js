export const localTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC";

export function importTimezones(selected = localTimezone) {
    return [...new Set(["UTC", localTimezone, selected, ...(Intl.supportedValuesOf?.("timeZone") ?? [])])].sort();
}
