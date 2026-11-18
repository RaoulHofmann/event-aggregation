const request = (origin, path) =>
    [
        `curl '${origin}${path}'`,
        '  -H "Authorization: Bearer $EVENTJUNCTION_TOKEN"',
        "  -H 'Accept: application/json'",
    ].join(" " + String.fromCharCode(92, 10));

export const apiExamples = (origin) => [
    {
        id: "all-sources",
        label: "All sources",
        path: "/api/v1/events?status=published&per_page=25",
        request: request(origin, "/api/v1/events?status=published&per_page=25"),
        response: JSON.stringify(
            {
                data: [
                    {
                        id: 42,
                        title: "Community garden workshop",
                        starts_at: "2026-11-21T02:00:00+00:00",
                        timezone: "Australia/Perth",
                        status: "published",
                        source: { provider: "eventnest", external_id: null },
                    },
                    {
                        id: 43,
                        title: "Local makers market",
                        starts_at: "2026-11-22T01:00:00+00:00",
                        timezone: "Australia/Perth",
                        status: "published",
                        source: {
                            provider: "humanitix",
                            external_id: "example-market",
                        },
                    },
                ],
                meta: { current_page: 1, per_page: 25, total: 2 },
            },
            null,
            2,
        ),
    },
    {
        id: "events",
        label: "Native events",
        path: "/api/v1/events?source=eventnest&status=published&per_page=25",
        request: request(
            origin,
            "/api/v1/events?source=eventnest&status=published&per_page=25",
        ),
        response: JSON.stringify(
            {
                data: [
                    {
                        id: 42,
                        title: "Community garden workshop",
                        starts_at: "2026-11-21T02:00:00+00:00",
                        ends_at: "2026-11-21T04:00:00+00:00",
                        timezone: "Australia/Perth",
                        location: "Fremantle Community Garden",
                        status: "published",
                        source: {
                            provider: "eventnest",
                            external_id: null,
                            local_overrides: [],
                            url: null,
                            synced_at: null,
                        },
                        custom_fields: { audience: "All experience levels" },
                    },
                ],
                meta: { current_page: 1, per_page: 25, total: 1 },
            },
            null,
            2,
        ),
    },
    {
        id: "occurrences",
        label: "Calendar occurrences",
        path: "/api/v1/occurrences?from=2026-11-01&to=2026-12-01&status=published",
        request: request(
            origin,
            "/api/v1/occurrences?from=2026-11-01&to=2026-12-01&status=published",
        ),
        response: JSON.stringify(
            {
                data: [
                    {
                        id: "42@2026-11-21",
                        event_id: 42,
                        occurrence_date: "2026-11-21",
                        title: "Community garden workshop",
                        starts_at: "2026-11-21T02:00:00+00:00",
                        ends_at: "2026-11-21T04:00:00+00:00",
                        timezone: "Australia/Perth",
                        all_day: false,
                        status: "published",
                        source: "eventnest",
                        recurring: true,
                    },
                ],
            },
            null,
            2,
        ),
    },
];
