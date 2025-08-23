// DataClasses.kt - API response data models
// Location: app/src/main/java/com/vetrx/clinic/DataClasses.kt
package com.vetrx.clinic

// Request models
data class OpenVisitRequest(val uid: String)

// Response models
data class OpenVisitResponse(
    val ok: Boolean,
    val visit: Visit,
    val pet: Pet,
    val owner: Owner
)

data class Visit(
    val id: Int,
    val uid: String,
    val date: String,
    val sequence: Int,
    val wasCreated: Boolean
)

data class Pet(
    val unique_id: String,
    val name: String,
    val species: String,
    val breed: String
)

data class Owner(
    val name: String,
    val mobile: String
)

data class TodayVisitsResponse(
    val ok: Boolean,
    val visits: List<VisitWithAttachments>
)

data class VisitWithAttachments(
    val id: Int,
    val sequence: Int,
    val date: String,
    val status: String,
    val attachments: List<Attachment>
)

data class Attachment(
    val id: Int,
    val type: String,
    val filename: String,
    val url: String,
    val size: Int,
    val note: String?
)

data class UploadResponse(
    val ok: Boolean,
    val visitId: Int,
    val attachment: Attachment
)
