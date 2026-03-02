<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Validator;

use Generated\Shared\Transfer\CommentRequestTransfer;
use Generated\Shared\Transfer\CommentValidationResponseTransfer;

/**
 * @deprecated Will be removed without replacement.
 */
interface QuoteCommentValidatorInterface
{
    public function validate(
        CommentRequestTransfer $commentRequestTransfer,
        CommentValidationResponseTransfer $commentValidationResponseTransfer
    ): CommentValidationResponseTransfer;
}
